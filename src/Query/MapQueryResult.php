<?php

namespace Drupal\ham_station\Query;

class MapQueryResult {

  private $subsquares;
  private $locations;
  private $mapCenterLat;
  private $mapCenterLng;
  private $queryCallsignIdx = NULL;

  /**
   * GridSquareCluster constructor.
   *
   * @param array $subsquares
   * @param null $map_center_lat
   * @param null $map_center_lng
   * @param array $locations
   */
  public function __construct(array $subsquares, $map_center_lat = NULL, $map_center_lng = NULL, $locations = [], $query_callsign_idx = NULL) {
    $this->subsquares = $subsquares;
    $this->mapCenterLat = $map_center_lat;
    $this->mapCenterLng = $map_center_lng;
    $this->locations = $locations;
    $this->queryCallsignIdx = $query_callsign_idx;
  }

  public function getSubsquares() {
    return $this->subsquares;
  }

  /**
   * @return null
   */
  public function getMapCenterLat() {
    return $this->mapCenterLat;
  }

  /**
   * @param null $mapCenterLat
   */
  public function setMapCenterLat($mapCenterLat) {
    $this->mapCenterLat = $mapCenterLat;
  }

  /**
   * @return null
   */
  public function getMapCenterLng() {
    return $this->mapCenterLng;
  }

  /**
   * @param null $mapCenterLng
   */
  public function setMapCenterLng($mapCenterLng) {
    $this->mapCenterLng = $mapCenterLng;
  }

  public function setLocations(array $locations) {
    $this->locations = $locations;
  }

  public function getLocations() {
    return $this->locations;
  }

  public function setQueryCallsignIdx($query_callsign_idx) {
    $this->queryCallsignIdx = $query_callsign_idx;
  }
  
  public function getQueryCallsignIdx() {
    return $this->queryCallsignIdx;
  }

}
