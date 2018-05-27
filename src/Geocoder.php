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
  const GEOCODE_MAX_RETRIES = 5;

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
   * The grid square service
   *
   * @var \Drupal\ham_station\GridService $grid_service
   */
  private $gridService;

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
   * @param \Drupal\ham_station\GridService $grid_service
   *   The grid square service.
   */
  public function __construct(
    ConfigFactory $config_factory,
    ClientInterface $http_client,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $db_connection,
    LoggerInterface $logger,
    CacheBackendInterface $cache,
    GridService $grid_service
  ) {
    $this->settings = $config_factory->get('ham_station.settings');
    $this->httpClient = $http_client;
    $this->hamStationStorage = $entity_type_manager->getStorage('ham_station');
    $this->dbConnection = $db_connection;
    $this->logger = $logger;
    $this->cache = $cache;
    $this->gridService = $grid_service;
  }

  /**
   * Geocode a batch of addresses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function geoCode(callable $callback = NULL) {
    $google_key = $this->settings->get('google_geocode_api_key');

    if (empty($google_key)) {
      throw new \Exception('Google geocode key is not set.');
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

    $success_count = 0;
    $not_found_count = 0;
    $error_count = 0;
    
    foreach ($entity_rows as $entity_row) {
      $entity = HamStation::load($entity_row->id);
      $url = $this->getGeoCodeUrl($entity, $google_key);

      $retries = 0;

      do {
        $response = NULL;
        $request_success = TRUE;

        try {
          $response = $this->httpClient->request('GET', $url);
        }
        catch (GuzzleException $ex) {
          $request_success = FALSE;
          $this->logger->warning(sprintf(
            "Http exception while geocoding %s %s",
            $entity->getCallsign(),
            $ex->getMessage()
          ));
        }

        if ($request_success && $response->getStatusCode() != 200) {
          $request_success = FALSE;
          $this->logger->warning(sprintf(
            'Status code %s while geocoding %s',
            $response->getReasonPhrase(),
            $entity->getCallsign()
          ));
        }

      } while (!$request_success && ++$retries < static::GEOCODE_MAX_RETRIES);

      if (!$request_success) {
        $msg = sprintf('Excessive http errors while geocoding %s.', $entity->getCallsign());
        $this->logger->error($msg);
        $this->printFeedback($msg, $callback);
        // Not much point in continuing.
        break;
      }

      $response_json = (string) $response->getBody();
      $response_data = Json::decode($response_json);

      $status = $response_data['status'];

      if ($status === 'OVER_QUERY_LIMIT') {
        $this->logger->info('Geocoding query limit exceeded');
        $this->printFeedback($status, $callback);
        // Try again tomorrow.
        break;
      }

      if ($status === 'REQUEST_DENIED') {
        // Not sure what this really means. Let's stop and investigate.
        $msg = sprintf('Geocoding request denied for %s.', $entity->getCallsign());
        $this->logger->error($msg);
        $this->printFeedback($msg, $callback);
        $error_count++;
        break;
      }

      if ($status === 'INVALID_REQUEST') {
        // This should never happen. Let's stop and investigate.
        $msg = sprintf('Invalid geocoding request for %s.', $entity->getCallsign());
        $this->logger->error($msg);
        $this->printFeedback($msg, $callback);
        $error_count++;
        break;
      }

      // Looking good so far. We didn't get an "abandon ship" error.

      switch ($status) {
        case 'OK':
          $location = $response_data['results'][0]['geometry']['location'];
          $entity->latitude = $location['lat'];
          $entity->longitude = $location['lng'];
          $entity->grid_square = $this->gridService->calculateGridSquare($location['lat'], $location['lng']);
          $entity->geocode_status = HamStation::GEOCODE_STATUS_SUCCESS;
          $success_count++;
          break;

        case 'ZERO_RESULTS';
          $entity->geocode_status = HamStation::GEOCODE_STATUS_NOT_FOUND;
          $not_found_count++;
          break;

        case 'UNKNOWN_ERROR':
          // Probably a server error. Let's log it and move on for now.
          $this->logger->error(sprintf('Unknown error was geocoding %s.', $entity->getCallsign()));
          $error_count++;
          break;

        default:
          // This will only happpen if Google has a new response. Let's log it
          // and move on for now.
          $this->logger->error(sprintf('New response status %s while geocoding %s.',
            $status,
            $entity->getCallsign()
          ));
          $error_count++;
          break;
      }

      // Save the response for debugging.
      $entity->geocode_response = $response_json;
      $entity->save();
    }
    
    $msg = sprintf(
      'Geocode results: Success: %s | Not found: %s | Errors: %s',
      $success_count,
      $not_found_count,
      $error_count
    );

    $this->logger->info($msg);
    $this->printFeedback($msg, $callback);

    // Invalidate cached counts.
    $this->cache->invalidate('ham_station_geocode_counts');
  }

  /**
   * Generate the URL for geocoding a station.\
   *
   * @param \Drupal\ham_station\Entity\HamStation $entity
   *   The entity.
   * @param string $google_key
   *   Google Geocode API key.
   *
   * @return string
   *   The URL.
   */
  private function getGeoCodeUrl(HamStation $entity, $google_key) {
    $address = $entity->address;
    $address1 = $address->address_line1;

    // Some addresses are like 123 ABC St, PO Box 100. Remove the PO Box.
    if (preg_match('/,\s*?((po)|(p\.o\.))\s+?box\s.*$/i', $address1, $matches) === 1) {
      $address1 = trim(str_replace($matches[0], '', $address1));
    }

    // See https://developers.google.com/maps/documentation/geocoding/start
    // This seems to be the correct format. i.e., postal code is not included
    // in the address. Adding it as a component filter seems to give a more
    // accurate response if the street address is not perfect.
    $url = Url::fromUri(
      'https://maps.googleapis.com/maps/api/geocode/json', [
        'query' => [
          'address' => sprintf('%s,%s,%s',
            $address1,
            $address->locality,
            $address->administrative_area
          ),
          'components' => sprintf('postal_code:%s|country:%s',
              $address->postalCode,
              $address->country_code
          ),
          'key' => $google_key,
        ],
      ]
    );

    return $url->toString();
  }

  /**
   * Copy geocode results to other licenses at the same address.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function copyGeocodeForDuplicates(callable $callback = NULL) {
    // This avoids wasting our Google query quota on duplicates.
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
