<?php

namespace Drupal\ham_station\GridSquares;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ham_station\DistanceService;
use Drupal\ham_station\Entity\HamAddress;
use Drupal\ham_station\Entity\HamStation;

/**
 * Service to provide subsquare calculations and factory.
 */
class GridSquareService {

  /**
   * Cache or subsquares keyed by code.
   *
   * @var array
   */
  private $subsquares = [];

  /**
   * Cache of subsquare clusters keyed by central code.
   *
   * @var array
   */
  private $gridClusters = [];

  const QUERY_LOCATION_CALLSIGN_NOT_FOUND = 1;
  const QUERY_LOCATION_CALLSIGN_NO_ADDRESS = 2;
  const QUERY_LOCATION_CALLSIGN_NO_GEO = 3;
  const QUERY_LOCATION_CALLSIGN_SUCCESS = 4;

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Database connection.
   *
   * @var Connection
   */
  private $dbConnection;

  /**
   * The distance service.
   *
   * @var DistanceService
   */
  private $distanceService;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $db_connection,
    DistanceService $distance_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dbConnection = $db_connection;
    $this->distanceService = $distance_service;
  }

  public function getMapDataByCallsign($callsign) {
    $result = $this->callsignQuery($callsign);

    if ($result['status'] !== static::QUERY_LOCATION_CALLSIGN_SUCCESS) {
      return NULL;
    }

    $lat = $result['lat'];
    $lng = $result['lng'];

    $grid_cluster = $this->getClusterFromLatLng($lat, $lng);
    $grid_cluster->setMapCenterLat($lat);
    $grid_cluster->setMapCenterLng($lng);

//    $grid_cluster->setMapCenterLat($grid_cluster->getCenter()->getLatCenter());
//    $grid_cluster->setMapCenterLng($grid_cluster->getCenter()->getLngCenter());

    $grid_cluster->setLocations($this->getStationsInRadius($lat, $lng, 20, 'miles'));
//    $grid_cluster->setStations($this->getStationsInRadius($grid_cluster->getCenter()->getLatCenter(), $grid_cluster->getCenter()->getLngCenter(), 5, 'miles'));

    return $grid_cluster;
  }

  /**
   * @param float $lat
   *   Latitude.
   * @param float $lng
   *   Longitude
   * @return null|string
   *   6 character subsquare code.
   */
  public function latLngToSubsquareCode($lat, $lng) {

    if (abs($lat) >= 90 || abs($lng) >= 180) {
      return NULL;
    }

    $lng += 180;
    $lat += 90;

    $upper_a = ord('A');
    $lower_a = ord('a');
    $zero = ord('0');

    $locator = [];

    $locator[] = chr($upper_a + ($lng / 20));
    $locator[] = chr($upper_a + ($lat / 10));

    $locator[] = chr($zero + (($lng % 20) / 2));
    $locator[] = chr($zero + ($lat % 10));

    $locator[] = chr($lower_a + fmod($lng, 2) * 12);
    $locator[] = chr($lower_a + fmod($lat, 1) * 24);

    return implode('', $locator);
  }

  /**
   * Create subsquare from code.
   *
   * @param string $code_upper
   *   Subsquare code.
   *
   * @return SubSquare
   *   A subsquare.
   */
  public function createSubsquareFromCode($code) {
    $code_uc = strtoupper($code);

    if (isset($this->subsquares[$code_uc])) {
      return $this->subsquares[$code_uc];
    }

    $upper_a = ord('A');
    $zero = ord('0');

    $lng = (ord($code_uc[0]) - $upper_a) * 20;
    $lat = (ord($code_uc[1]) - $upper_a) * 10;

    $lng += (ord($code_uc[2]) - $zero) * 2;
    $lat += (ord($code_uc[3]) - $zero);

    $lng += (ord($code_uc[4]) - $upper_a) / 12;
    $lat += (ord($code_uc[5]) - $upper_a) / 24;

    $lng_west = $lng - 180;
    $lng_east = $lng_west + (1/12);
    $lng_center = ($lng_east + $lng_west) / 2;

    $lat_south = $lat - 90;
    $lat_north = $lat_south + (1/24);
    $lat_center = ($lat_north + $lat_south) / 2;

    $subsquare = new Subsquare($code, $lat_south, $lat_north, $lat_center, $lng_west, $lng_east, $lng_center);

    $this->subsquares[$code_uc] = $subsquare;
    return $subsquare;
  }

  /**
   * Create subsquare from lat and lng.
   *
   * @param float $lat
   *   Latitude.
   * @param float $lng
   *   Longatude.
   * @return SubSquare
   *   Subsquare.
   */
  public function createSubsquareFromLatLng($lat, $lng) {
    $code = $this->latLngToSubsquareCode($lat, $lng);
    return $this->createSubsquareFromCode($code);
  }

  /**
   * Get a cluster of neighboring subsquares.
   *
   * @param Subsquare $subsquare
   *   Central subsquare.
   *
   * @return GridSquareCluster
   *   Cluster of 9 subsquares.
   */
  public function getCluster(Subsquare $subsquare) {
    $code_uc = strtoupper($subsquare->getCode());
    
    if (isset($this->gridClusters[$code_uc])) {
      return $this->gridClusters[$code_uc];
    }

    $delta = 0.01;
    
    $cluster = new GridSquareCluster(
      $subsquare,
      $this->createSubsquareFromLatLng($subsquare->getLatNorth() + $delta, $subsquare->getLngWest() - $delta),
      $this->createSubsquareFromLatLng($subsquare->getLatNorth() + $delta, $subsquare->getLngCenter()),
      $this->createSubsquareFromLatLng($subsquare->getLatNorth() + $delta, $subsquare->getLngEast() + $delta),
      $this->createSubsquareFromLatLng($subsquare->getLatCenter(), $subsquare->getLngEast() + $delta),
      $this->createSubsquareFromLatLng($subsquare->getLatSouth() - $delta, $subsquare->getLngEast() + $delta),
      $this->createSubsquareFromLatLng($subsquare->getLatSouth() - $delta, $subsquare->getLngCenter()),
      $this->createSubsquareFromLatLng($subsquare->getLatSouth() - $delta, $subsquare->getLngWest() - $delta),
      $this->createSubsquareFromLatLng($subsquare->getLatCenter(), $subsquare->getLngWest() - $delta)
    );
    
    $this->gridClusters[$code_uc] = $cluster;
    return $cluster;
  }

  private function getClusterFromLatLng($lat, $lng) {
    $center_subsquare = $this->createSubsquareFromLatLng($lat, $lng);
    return $this->getCluster($center_subsquare);
  }

  private function callsignQuery($callsign) {

    $storage = $this->entityTypeManager->getStorage('ham_station');

    $entity_ids = $storage
      ->getQuery()
      ->condition('callsign', $callsign)
      ->execute();

    $return = ['callsign' => $callsign];

    if (empty($entity_ids)) {
      return $return + ['status' => static::QUERY_LOCATION_CALLSIGN_NOT_FOUND];
    }

    /** @var HamStation $ham_station */
    $ham_station = $storage->load(reset($entity_ids));

    $storage = $this->entityTypeManager->getStorage('ham_address');

    $entity_ids = $storage
      ->getQuery()
      ->condition('hash', $ham_station->get('address_hash')->value)
      ->execute();

    if (empty($entity_ids)) {
      return $return + ['status' => static::QUERY_LOCATION_CALLSIGN_NO_ADDRESS];
    }

    /** @var HamAddress $ham_address */
    $ham_address = $storage->load(reset($entity_ids));

    if ($ham_address->get('geocode_status')->value != HamAddress::GEOCODE_STATUS_SUCCESS) {
      return $return + ['status' => static::QUERY_LOCATION_CALLSIGN_NO_GEO];
    }

    return $return + [
      'status' => static::QUERY_LOCATION_CALLSIGN_SUCCESS,
      'lat' => (float) $ham_address->get('latitude')->value,
      'lng' => (float) $ham_address->get('longitude')->value
    ];
  }

  public function getStationsInRadius($lat, $lng, $radius, $units) {
    $st = microtime(true);

    $address_alias = 'ha';
    $distance_formula = $this->distanceService->getDistanceFormula($lat, $lng, $units, $address_alias);
    $box_formula = $this->distanceService->getBoundingBoxFormula($lat, $lng, $radius, $units, $address_alias);

    $query = $this->dbConnection->select('ham_address', $address_alias);
    $query->fields($address_alias, ['id', 'hash', 'address__address_line1', 'address__address_line2', 'address__locality', 'address__administrative_area', 'address__postal_code', 'latitude', 'longitude']);
    $query->addExpression($distance_formula, 'distance');

    $query->where($box_formula);
    $query->where($distance_formula . ' < :radius', [':radius' => $radius]);
    $query->range(0, 200);
    $query->orderBy('distance');

    $result = [];
    $stmt = $query->execute();

    // map address_id to $result index. This means $result can be a sequencial
    // array rather than associative. This makes it an array when serialized
    // to json.
    $indexMap = [];
    $hashes = [];

    foreach ($stmt as $row) {
      if (!isset($indexMap[$row->id])) {
        $result[] = new HamAddressDTO(
          $row->address__address_line1,
          $row->address__address_line2,
          $row->address__locality,
          $row->address__administrative_area,
          $row->address__postal_code,
          (float) $row->latitude,
          (float) $row->longitude
        );

        $idx = count($result) - 1;
        $indexMap[$row->id] = $idx;
        $hashes[$row->hash] = $idx;
      }
    }

    $query = $this->dbConnection->select('ham_station', 'hs');
    $query->fields('hs', ['address_hash', 'callsign', 'first_name', 'middle_name', 'last_name', 'suffix', 'organization', 'operator_class']);
    $query->condition('address_hash', array_keys($hashes), 'IN');
    $stmt = $query->execute();

    foreach ($stmt as $row) {
      $result[$hashes[$row->address_hash]]->addStation(new HamStationDTO(
        $row->callsign,
        $row->first_name,
        $row->middle_name,
        $row->last_name,
        $row->suffix,
        $row->organization,
        $row->operator_class
      ));
    }

    $et = microtime(true) - $st;
    return $result;
  }

}
