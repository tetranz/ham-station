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
 * Class to geocode addresses using OpenStreetMap / Nominatim
 */
class OSMGeocoder {

  /**
   * Number of times to retry a bad http response.
   */
  const GEOCODE_MAX_RETRIES = 1;

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
   * Geocoder constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Guzzle http client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Database $db_connection
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    ClientInterface $http_client,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $db_connection,
    LoggerInterface $logger
  ) {
    $this->httpClient = $http_client;
    $this->hamStationStorage = $entity_type_manager->getStorage('ham_station');
    $this->dbConnection = $db_connection;
    $this->logger = $logger;
  }

  /**
   * Geocode a batch of addresses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function geoCode($id_suffix, callable $callback = NULL) {

    // Get a batch of entities with pending geocode status.
    $query = $this->dbConnection->select('ham_station', 'hs')
      ->fields('hs', ['id']);

    $query->condition('hs.osm_geocode_status', HamStation::GEOCODE_STATUS_PENDING);
    $query->where('right(concat(\'000000\', id), :suffix_length) = :suffix', [
      ':suffix_length' => strlen($id_suffix),
      ':suffix' => $id_suffix,
    ]);

    $entity_rows = $query->execute();

    $success_count = 0;
    $not_found_count = 0;
    $count = 0;

    foreach ($entity_rows as $entity_row) {
      $entity = HamStation::load($entity_row->id);
      $entities[] = $entity;
      $address = $entity->address;
      $address1 = $address->address_line1;

      // Some addresses are like 123 ABC St, PO Box 100. Remove the PO Box.
      if (preg_match('/,\s*?((po)|(p\.o\.))\s+?box\s.*$/i', $address1, $matches) === 1) {
        $address1 = trim(str_replace($matches[0], '', $address1));
      }

      $address_query = sprintf(
        '%s,%s,%s,%s',
        $address1,
        $address->locality,
        $address->administrative_area,
        $address->postal_code
      );

      $retries = self::GEOCODE_MAX_RETRIES;

      do {
        $response = NULL;
        $request_success = FALSE;

        try {
          $response = $this->httpClient->request('GET', 'http://geo.tetranz.com/nominatim/search.php', [
            'query' => [
              'q' => $address_query,
              'format' => 'json',
              'limit' => 1,
            ],
          ]);
          $request_success = TRUE;
        }
        catch (GuzzleException $ex) {
          $this->logger->warning(sprintf(
            "Http exception calling Nominatim %s",
            $ex->getMessage()
          ));
        }

        if ($request_success && $response->getStatusCode() != 200) {
          $request_success = FALSE;
          $this->logger->warning(sprintf(
            'Status code %s from Nominatim',
            $response->getStatusCode()
          ));
        }

      } while (!$request_success && ++$retries < static::GEOCODE_MAX_RETRIES);

      if (!$request_success) {
        $msg = sprintf('Excessive http errors calling Nominatim');
        $this->logger->error($msg);
        $this->printFeedback($msg, $callback);
        // Not much point in continuing.
        break;
      }

      $response_json = (string) $response->getBody();
      $entity->osm_geocode_response = $response_json;

      $response_data = Json::decode($response_json);

      if (!empty($response_data)) {
        $response_data = $response_data[0];
        $entity->osm_latitude = $response_data['lat'];
        $entity->osm_longitude = $response_data['lon'];
        $entity->osm_geocode_status = HamStation::GEOCODE_STATUS_SUCCESS;
        $success_count++;
      }
      else {
        $entity->osm_geocode_status = HamStation::GEOCODE_STATUS_NOT_FOUND;
        $not_found_count++;
      }

      $entity->save();

      $count++;
      if ($count % 100 == 0) {
        $this->printFeedback($count, $callback);
      }
    }

    $msg = sprintf(
      'OSM geocode results: Success: %s | Not found: %s',
      $success_count,
      $not_found_count
    );

    $this->logger->info($msg);
    $this->printFeedback($msg, $callback);
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
