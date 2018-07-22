<?php

namespace Drupal\ham_station;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ham_station\Entity\HamStation;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Class to geocode addresses.
 */
class Geocoder {

  /**
   * Number of times to retry a bad http response.
   */
  const GEOCODE_MAX_RETRIES = 1;

  /**
   * The ham_station settings
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  private $hamStationStorage;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $dbConnection;

  /**
   * The Cache
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * Geocoder constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Guzzle http client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Database $db_connection
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   */
  public function __construct(
    ConfigFactory $config_factory,
    ClientInterface $http_client,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $db_connection,
    LoggerInterface $logger,
    CacheBackendInterface $cache
  ) {
    $this->settings = $config_factory->get('ham_station.settings');
    $this->httpClient = $http_client;
    $this->hamStationStorage = $entity_type_manager->getStorage('ham_station');
    $this->dbConnection = $db_connection;
    $this->logger = $logger;
    $this->cache = $cache;
  }

  /**
   * Geocode a batch of addresses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function geoCode(callable $callback = NULL) {
    $geocodio_key = $this->settings->get('geocodio_api_key');

    if (empty($geocodio_key)) {
      throw new \Exception('Geocodio key is not set.');
    }

    $batch_size = $this->settings->get('geocode_batch_size');

    if (!is_numeric($batch_size)) {
      throw new \Exception('Geocode batch size is not set.');
    }

    // Get a batch of entities with pending geocode status.
    // Where there are multiple stations at the same address, get only one.
    // Don't get stations where we have already successfully geocoded the same
    // address. We will fill these in with another query.
    $query = $this->dbConnection->select('ham_station', 'hs')
      ->fields('hs', ['id']);

    $query->condition('hs.geocode_status', HamStation::GEOCODE_STATUS_PENDING)
      ->where('hs.id = (SELECT MIN(hs2.id) FROM {ham_station} hs2 WHERE hs2.address_hash = hs.address_hash)')
      ->where('NOT EXISTS (SELECT * FROM {ham_station} hs3 WHERE hs3.address_hash = hs.address_hash AND hs3.geocode_status = :success_status)', [
        ':success_status' => HamStation::GEOCODE_STATUS_SUCCESS
      ]);

    // Process states in alphabetical order.
    $query
      ->orderBy('hs.address__administrative_area');

    $query->range(0, $batch_size);

    // Using this mostly to get started with experimental queries.
    $extra_where = $this->settings->get('extra_batch_query_where');
    if (!empty($extra_where)) {
      $query->where($extra_where);
    }

    $entity_rows = $query->execute();
    $request_data = [];
    $entities = [];

    foreach ($entity_rows as $entity_row) {
      $entity = HamStation::load($entity_row->id);
      $entities[] = $entity;
      $address = $entity->address;
      $address1 = $address->address_line1;

      // Some addresses are like 123 ABC St, PO Box 100. Remove the PO Box.
      if (preg_match('/,\s*?((po)|(p\.o\.))\s+?box\s.*$/i', $address1, $matches) === 1) {
        $address1 = trim(str_replace($matches[0], '', $address1));
      }

      $request_data[] = [
        'street' => $address1,
        'city' => $address->locality,
        'state' => $address->administrative_area,
        'postal_code' => $address->postal_code,
      ];
    }

    $retries = 0;

    do {
      $response = NULL;
      $request_success = TRUE;

      try {
        // Geocodio batch request.
        $response = $this->httpClient->request('POST', 'https://api.geocod.io/v1.3/geocode', [
          'json' => $request_data,
          'query' => ['api_key' => $geocodio_key],
        ]);
      }
      catch (GuzzleException $ex) {
        $request_success = FALSE;
        $this->logger->warning(sprintf(
          "Http exception calling Geocodio %s",
          $ex->getMessage()
        ));
      }

      if ($request_success && $response->getStatusCode() != 200) {
        $request_success = FALSE;
        $this->logger->warning(sprintf(
          'Status code %s from Geocodio',
          $response->getStatusCode()
        ));
      }

    } while (!$request_success && ++$retries < static::GEOCODE_MAX_RETRIES);

    if (!$request_success) {
      $msg = sprintf('Excessive http errors calling Geocodio');
      $this->logger->error($msg);
      $this->printFeedback($msg, $callback);
      // Not much point in continuing.
      return;
    }

    $response_json = (string) $response->getBody();
    $response_data = Json::decode($response_json);
    $all_entities_results = $response_data['results'];

    $success_count = 0;
    $not_found_count = 0;

    // Anything else is effectively "not found".
    $ok_accuracy_types = ['rooftop', 'point', 'range_interpolation'];

    foreach ($entities as $idx => $entity) {
      if ($this->updateEntityWithResult($entity, $all_entities_results[$idx], $ok_accuracy_types)) {
        $success_count++;
      }
      else {
        $not_found_count++;
      }

      $entity->save();
    }

    $msg = sprintf(
      'Geocode results: Success: %s | Not found: %s',
      $success_count,
      $not_found_count
    );

    $this->logger->info($msg);
    $this->printFeedback($msg, $callback);

    // Invalidate cached counts.
    $this->cache->invalidate('ham_station_geocode_counts');
  }

  private function updateEntityWithResult(HamStation $entity, array $entity_result, array $ok_accuracy_types) {
    $entity->geocode_provider = 'ge';
    $entity->geocode_response = Json::encode($entity_result);

    if (empty($entity_result['response']['results'])) {
      $entity->geocode_status = HamStation::GEOCODE_STATUS_NOT_FOUND;
      return FALSE;
    }

    $best_result = NULL;
    $best_score = NULL;

    foreach ($entity_result['response']['results'] as $result) {
      if (!in_array($result['accuracy_type'], $ok_accuracy_types)) {
        continue;
      }

      if (empty($best_result) || $result['accuracy'] > $best_score) {
        $best_result = $result;
      }
    }

    if (empty($best_result)) {
      $entity->geocode_status = HamStation::GEOCODE_STATUS_NOT_FOUND;
      return FALSE;
    }

    $entity->latitude = $best_result['location']['lat'];
    $entity->longitude = $best_result['location']['lat'];
    $entity->geocode_status = HamStation::GEOCODE_STATUS_SUCCESS;

    return TRUE;
  }

  /**
   * Copy geocode results to other licenses at the same address.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function copyGeocodeForDuplicates(callable $callback = NULL) {
    // This avoids wasting our Geocoding query quota on duplicates.
    $query = $this->dbConnection->select('ham_station', 'hs1');
    $query->addField('hs1', 'id', 'done_id');
    $query->addField('hs2', 'id', 'other_id');

    $query->innerJoin(
      'ham_station',
      'hs2',
      'hs2.address_hash = hs1.address_hash AND hs2.geocode_status = :pending_status',
      [':pending_status' => HamStation::GEOCODE_STATUS_PENDING]
    );

    $rows = $query->condition('hs1.geocode_status', HamStation::GEOCODE_STATUS_PENDING, '<>')
      ->orderBy('hs1.id')
      ->execute();

    /** @var HamStation $done_entity */
    $done_entity = NULL;
    $update_count = 0;

    foreach ($rows as $row) {
      if (empty($done_entity) || $done_entity->id() != $row->done_id) {
        $done_entity = HamStation::load($row->done_id);
      }

      /** @var HamStation $other_entity */
      $other_entity = HamStation::load($row->other_id);

      $other_entity->latitude = $done_entity->latitude;
      $other_entity->longitude = $done_entity->longitude;
      $other_entity->geocode_response = $done_entity->geocode_response;
      $other_entity->geocode_status = $done_entity->geocode_status;
      $other_entity->save();
      $update_count++;
    }

    $msg = sprintf(
      '%s geocode results copied to duplicate addresses',
      $update_count
    );

    $this->logger->info($msg);
    $this->printFeedback($msg, $callback);
  }

  /**
   * Reload lat and lng from json response.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function reloadLatLng(callable $callback = NULL) {
    // This is probably only used once, to load the new lat and lng base fields
    // from the original json response.
    $query = $this->dbConnection->select('ham_station', 'hs');
    $query->addField('hs', 'id');
    $query->addField('hs', 'geocode_response');
    $query->condition('hs.geocode_status', HamStation::GEOCODE_STATUS_SUCCESS);
    $query->isNull('latitude');
    $query->range(0, 5000);
    $rows = $query->execute();

    $count = 0;
    foreach ($rows as $row) {
      $response_data = Json::decode($row->geocode_response);
      $location = $response_data['results'][0]['geometry']['location'];
      $entity = HamStation::load($row->id);
      $entity->latitude = $location['lat'];
      $entity->longitude = $location['lng'];
      $entity->save();
      $count++;
    }

    $this->printFeedback($count, $callback);
  }

  /**
   * Print feedback if a callback from supplied. Use for drush commands.
   * 
   * @param string $message
   *   The message to print.
   * @param callable|NULL $callback
   *   Callback.
   */
  private function printFeedback($message, callable $callback = NULL) {
    if ($callback !== NULL) {
      $callback($message);
    }
  }
}
