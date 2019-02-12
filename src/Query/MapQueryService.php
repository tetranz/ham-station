<?php

namespace Drupal\ham_station\Query;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ham_station\DistanceService;
use Drupal\ham_station\GoogleGeocoder;

/**
 * Service to provide data for the map.
 */
class MapQueryService {

  /**
   * Cache or subsquares keyed by code.
   *
   * @var array
   */
  private $subsquares = [];

  private $errorMessage;

  const DIRECTION_NORTH = 0;
  const DIRECTION_EAST = 1;
  const DIRECTION_SOUTH = 2;
  const DIRECTION_WEST = 3;

  const GEOCODE_STATUS_PENDING = 0;
  const GEOCODE_STATUS_SUCCESS = 1;
  const GEOCODE_STATUS_NOT_FOUND = 2;
  const GEOCODE_STATUS_PO_BOX = 3;

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

    if (isset($result['error'])) {
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
    list($locations, $query_callsign_idx) = $this->getStationsInRadius($lat, $lng, 20, 'miles', $callsign);
    return new MapQueryResult($this->buildSubsquares($lat, $lng), $lat, $lng, $locations, $query_callsign_idx);
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

  private function callsignQuery($callsign) {
    $query = $this->dbConnection->select('ham_station', 'hs');
    $query->addJoin('INNER', 'ham_address', 'ha', 'ha.hash = hs.address_hash');
    $query->addJoin('LEFT', 'ham_location', 'hl', 'hl.id = ha.location_id');
    $query->fields('ha', ['geocode_status']);
    $query->fields('hl', ['latitude', 'longitude']);
    $query->condition('hs.callsign', $callsign);

    $result = $query->execute()->fetch();

    if ($result === FALSE) {
      return ['error' => sprintf('We have no record of callsign %s.', $callsign)];
    }

    if ($result->geocode_status == self::GEOCODE_STATUS_PENDING) {
      return ['error' => sprintf('The address for %s has not been geocoded yet.', $callsign)];
    }

    if ($result->geocode_status == self::GEOCODE_STATUS_NOT_FOUND) {
      return ['error' => sprintf('The address for %s could not be geocoded.', $callsign)];
    }

    if ($result->geocode_status == self::GEOCODE_STATUS_PO_BOX) {
      return ['error' => sprintf('The address for %s is a PO Box.', $callsign)];
    }

    return [
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
      return [$result, NULL];
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

    $query_callsign_idx = NULL;

    if (!empty($callsign_idx)) {
      // This puts the queried callsign on the marker label if there
      // are more than one callsign at the location.
      list($result_idx, $address_idx, $station_idx) = $callsign_idx;

      /** @var HamLocationDTO $location */
      $location = $result[$result_idx];
      $address = $location->moveAddressToTop($address_idx);
      $address->moveStationToTop($station_idx);
      $query_callsign_idx = $result_idx;
    }

    return [$result, $query_callsign_idx];
  }

  public function getErrorMessage() {
    return $this->errorMessage;
  }

  private function buildSubsquares($lat, $lng) {
    $code = $this->latLngToSubsquareCode($lat, $lng);
    $subsquares = [0 => [0 => $this->createSubsquareFromCode($code)]];

    for ($i = 0; $i < 5; $i++) {
      $subsquares = $this->enlargeCluster($subsquares);
    }

    return $subsquares;
  }

  private function enlargeCluster(array $cluster) {
    $north_side = $this->buildNorthSide($cluster);
    $east_side = $this->buildEastSide($cluster);
    $south_side = $this->buildSouthSide($cluster);
    $west_side = $this->buildWestSide($cluster);
    $corners = $this->buildCorners($cluster);

    for ($x = 0; $x < count($cluster); $x++) {
      array_unshift($cluster[$x], $south_side[$x]);
      array_push($cluster[$x], $north_side[$x]);
    }

    array_unshift($west_side, $corners[2]);
    array_push($west_side, $corners[3]);
    array_unshift($cluster, $west_side);

    array_unshift($east_side, $corners[1]);
    array_push($east_side, $corners[0]);
    array_push($cluster, $east_side);

    return $cluster;
  }

  private function buildNorthSide($cluster) {
    $y = count($cluster[0]) - 1;
    $edge = [];
    for ($x = 0; $x < count($cluster); $x++) {
      $edge[] = $this->buildNextSubsquare($cluster[$x][$y], self::DIRECTION_NORTH);
    }
    return $edge;
  }

  private function buildEastSide($cluster) {
    $x = count($cluster) - 1;
    $edge = [];
    for ($y = 0; $y < count($cluster[0]); $y++) {
      $edge[] = $this->buildNextSubsquare($cluster[$x][$y], self::DIRECTION_EAST);
    }
    return $edge;
  }

  private function buildSouthSide($cluster) {
    $y = 0;
    $edge = [];
    for ($x = 0; $x < count($cluster); $x++) {
      $edge[] = $this->buildNextSubsquare($cluster[$x][$y], self::DIRECTION_SOUTH);
    }
    return $edge;
  }

  private function buildWestSide($cluster) {
    $edge = [];
    for ($y = 0; $y < count($cluster[0]); $y++) {
      $edge[] = $this->buildNextSubsquare($cluster[0][$y], self::DIRECTION_WEST);
    }
    return $edge;
  }

  private function buildCorners(array $cluster) {
    $delta = 0.01;
    $max_idx = count($cluster) - 1;
    $corners = [];

    $ne = $cluster[$max_idx][$max_idx];
    $corners[] = $this->createSubsquareFromLatLng($ne->getLatNorth() + $delta, $ne->getLngEast() + $delta);

    $se = $cluster[$max_idx][0];
    $corners[] = $this->createSubsquareFromLatLng($se->getLatSouth() - $delta, $se->getLngEast() + $delta);

    $sw = $cluster[0][0];
    $corners[] = $this->createSubsquareFromLatLng($sw->getLatSouth() - $delta, $sw->getLngWest() - $delta);

    $nw = $cluster[0][$max_idx];
    $corners[] = $this->createSubsquareFromLatLng($nw->getLatNorth() + $delta, $nw->getLngWest() - $delta);

    return $corners;
  }

  /**
   * @param Subsquare $subsquare
   * @param $direction
   * @return SubSquare
   */
  private function buildNextSubsquare(Subsquare $subsquare, $direction) {
    $lat = $subsquare->getLatCenter();
    $lng = $subsquare->getLngCenter();
    $delta = 0.01;

    switch ($direction) {
      case self::DIRECTION_NORTH:
        $lat = $subsquare->getLatNorth() + $delta;
        break;
      case self::DIRECTION_EAST:
        $lng = $subsquare->getLngEast() + $delta;
        break;
      case self::DIRECTION_SOUTH:
        $lat = $subsquare->getLatSouth() - $delta;
        break;
      case self::DIRECTION_WEST:
        $lng = $subsquare->getLngWest() - $delta;
        break;
    }

    return $this->createSubsquareFromLatLng($lat,$lng);
  }

}
