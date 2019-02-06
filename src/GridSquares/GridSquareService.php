<?php

namespace Drupal\ham_station\GridSquares;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ham_station\DistanceService;
use Drupal\ham_station\Entity\HamAddress;
use Drupal\ham_station\Entity\HamStation;
use Drupal\ham_station\Geocodio;
use Drupal\ham_station\GoogleGeocoder;

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

  private $errorMessage;

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

  /**
   * @var GoogleGeocoder
   */
  private $googleGeocoder;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $db_connection,
    DistanceService $distance_service,
    GoogleGeocoder $google_geocoder
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dbConnection = $db_connection;
    $this->distanceService = $distance_service;
    $this->googleGeocoder = $google_geocoder;
  }

  public function mapQuery($query_type, $query_value) {
    if ($query_type === 'c') {
      return $this->getMapDataByCallsign($query_value);
    }

    if ($query_type === 'g') {
      return $this->getMapDataByGridsquare($query_value);
    }

    if ($query_type === 'z') {
      return $this->getMapDataByZipCode($query_value);
    }

    if ($query_type == 'latlng') {
      $parts = explode(',', $query_value);

      return $this->getMapDataCentered((float) $parts[0], (float) $parts[1]);
    }
  }

  private function getMapDataByCallsign($callsign) {
    $callsign = strtoupper($callsign);
    $result = $this->callsignQuery($callsign);

    if ($result['status'] !== static::QUERY_LOCATION_CALLSIGN_SUCCESS) {
      $this->errorMessage = $result['error'];
      return NULL;
    }

    return $this->getMapDataCentered($result['lat'], $result['lng'], $callsign);
  }

  private function getMapDataByGridsquare($code) {
    $center_square = $this->createSubsquareFromCode($code);
    return $this->getMapDataCentered($center_square->getLatCenter(), $center_square->getLngCenter());
  }

  private function getMapDataByZipCode($zipcode) {
    $result = $this->googleGeocoder->geocodePostalCode($zipcode);
    
    if (empty($result)) {
      $this->errorMessage = t('We can\'t find a location for that zip code.');
      return NULL;
    }

    return $this->getMapDataCentered($result['lat'], $result['lng']);
  }

  private function getMapDataCentered($lat, $lng, $callsign = NULL) {
    $grid_cluster = $this->getClusterFromLatLng($lat, $lng);
    $grid_cluster->setMapCenterLat($lat);
    $grid_cluster->setMapCenterLng($lng);
    list($locations, $redraw_location_id) = $this->getStationsInRadius($lat, $lng, 20, 'miles', $callsign);
    $grid_cluster->setLocations($locations);
    $grid_cluster->setRedrawLocationId($redraw_location_id);
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
    $query = $this->dbConnection->select('ham_station', 'hs');
    $query->addJoin('INNER', 'ham_address', 'ha', 'ha.hash = hs.address_hash');
    $query->addJoin('INNER', 'ham_location', 'hl', 'hl.id = ha.location_id');
    $query->fields('hl', ['latitude', 'longitude']);
    $query->condition('hs.callsign', $callsign);

    $result = $query->execute()->fetch();

    $return = ['callsign' => $callsign];

    if ($result === FALSE) {
      return $return + [
        'status' => static::QUERY_LOCATION_CALLSIGN_NO_ADDRESS,
        'error' => sprintf('We were unable to geocode the location of callsign %s.', $callsign),
      ];
    }

    return $return + [
      'status' => static::QUERY_LOCATION_CALLSIGN_SUCCESS,
      'lat' => (float) $result->latitude,
      'lng' => (float) $result->longitude
    ];
  }

  private function getStationsInRadius($lat, $lng, $radius, $units, $callsign) {
    $location_alias = 'hl';
    $distance_formula = $this->distanceService->getDistanceFormula($lat, $lng, $units, $location_alias);
    $box_formula = $this->distanceService->getBoundingBoxFormula($lat, $lng, $radius, $units, $location_alias);

    $query = $this->dbConnection->select('ham_location', $location_alias);
    $query->fields($location_alias, ['id', 'latitude', 'longitude']);
    $query->addExpression($distance_formula, 'distance');
    $query->where($box_formula);
    $query->where($distance_formula . ' < :radius', [':radius' => $radius]);
    $query->range(0, 200);
    $query->orderBy('distance');

    $result = [];
    $stmt = $query->execute();

    $location_map = [];
    $idx = -1;
    foreach ($stmt as $row) {
      $result[] = new HamLocationDTO(
        (int) $row->id,
        (float) $row->latitude,
        (float) $row->longitude
      );
      $location_map[$row->id] = ++$idx;
    }

    if (empty($result)) {
      return $result;
    }

    $address_alias = 'ha';
    $query = $this->dbConnection->select('ham_address', $address_alias);
    $query->fields($address_alias, ['id', 'address__address_line1', 'address__address_line2', 'address__locality', 'address__administrative_area', 'address__postal_code', 'location_id']);
    $query->addJoin('INNER', 'ham_station', 'hs', 'hs.address_hash = ha.hash');
    $query->fields('hs', ['callsign', 'first_name', 'middle_name', 'last_name', 'suffix', 'organization', 'operator_class']);
    $query->condition('ha.location_id', array_keys($location_map), 'IN');
    $stmt = $query->execute();

    $address_map = [];
    $callsign_idx = NULL;

    foreach ($stmt as $row) {
      $result_idx = $location_map[$row->location_id];
      /** @var HamLocationDTO $location */
      $location = $result[$result_idx];

      if (!isset($address_map[$row->id])) {
        $address = new HamAddressDTO(
          $row->address__address_line1,
          $row->address__address_line2,
          $row->address__locality,
          $row->address__administrative_area,
          $row->address__postal_code
        );
        $location->addAddress($address);
        $address_map[$row->id] = count($location->getAddresses()) - 1;
      }
      else {
        $address = $location->getAddresses()[$address_map[$row->id]];
      }

      $address->addStation(
        new HamStationDTO(
          $row->callsign,
          $row->first_name,
          $row->middle_name,
          $row->last_name,
          $row->suffix,
          $row->organization,
          $row->operator_class
        )
      );

      if (!empty($callsign) && empty($callsign_idx) && $row->callsign === $callsign) {
        $callsign_idx = [$result_idx, $address_map[$row->id], count($address->getStations()) - 1];
      }
    }

    $redraw_location_id = NULL;

    if (!empty($callsign_idx)) {
      list($result_idx, $address_idx, $station_idx) = $callsign_idx;

      /** @var HamLocationDTO $location */
      $location = $result[$result_idx];
      $address = $location->moveAddressToTop($address_idx);
      $address->moveStationToTop($station_idx);
      $redraw_location_id = $location->getId();
    }

    return [$result, $redraw_location_id];
  }

  public function getErrorMessage() {
    return $this->errorMessage;
  }

}
