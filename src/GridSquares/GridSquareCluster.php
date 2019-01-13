<?php

namespace Drupal\ham_station\GridSquares;

class GridSquareCluster {

  private $center;
  private $northWest;
  private $north;
  private $northEast;
  private $east;
  private $southEast;
  private $south;
  private $southWest;
  private $west;
  private $stations;
  private $mapCenterLat;
  private $mapCenterLng;

  /**
   * GridSquareCluster constructor.
   *
   * @param $center
   * @param $northWest
   * @param $north
   * @param $northEast
   * @param $east
   * @param $southEast
   * @param $south
   * @param $southWest
   * @param $west
   */
  public function __construct($center, $northWest, $north, $northEast, $east, $southEast, $south, $southWest, $west, $map_center_lat = NULL, $map_center_lng = NULL, $stations = [])
  {
    $this->center = $center;
    $this->northWest = $northWest;
    $this->north = $north;
    $this->northEast = $northEast;
    $this->east = $east;
    $this->southEast = $southEast;
    $this->south = $south;
    $this->southWest = $southWest;
    $this->west = $west;
    $this->mapCenterLat = $map_center_lat;
    $this->mapCenterLng = $map_center_lng;
    $this->stations = $stations;
  }

  /**
   * @return Subsquare
   */
  public function getCenter()
  {
    return $this->center;
  }

  /**
   * @return Subsquare
   */
  public function getNorthWest()
  {
    return $this->northWest;
  }

  /**
   * @return Subsquare
   */
  public function getNorth()
  {
    return $this->north;
  }

  /**
   * @return Subsquare
   */
  public function getNorthEast()
  {
    return $this->northEast;
  }

  /**
   * @return Subsquare
   */
  public function getEast()
  {
    return $this->east;
  }

  /**
   * @return Subsquare
   */
  public function getSouthEast()
  {
    return $this->southEast;
  }

  /**
   * @return Subsquare
   */
  public function getSouth()
  {
    return $this->south;
  }

  /**
   * @return Subsquare
   */
  public function getSouthWest()
  {
    return $this->southWest;
  }

  /**
   * @return Subsquare
   */
  public function getWest()
  {
    return $this->west;
  }

  /**
   * @return null
   */
  public function getMapCenterLat()
  {
    return $this->mapCenterLat;
  }

  /**
   * @param null $mapCenterLat
   */
  public function setMapCenterLat($mapCenterLat)
  {
    $this->mapCenterLat = $mapCenterLat;
  }

  /**
   * @return null
   */
  public function getMapCenterLng()
  {
    return $this->mapCenterLng;
  }

  /**
   * @param null $mapCenterLng
   */
  public function setMapCenterLng($mapCenterLng)
  {
    $this->mapCenterLng = $mapCenterLng;
  }

  public function setStations(array $stations) {
    $this->stations = $stations;
  }

  public function getStations() {
    return $this->stations;
  }

}
