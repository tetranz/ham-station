<?php

namespace Drupal\ham_station\Query;

/**
 * Represents a grid subsquare.
 */
class Subsquare {

  private $code;
  private $lat_north;
  private $lat_center;
  private $lat_south;
  private $lng_east;
  private $lng_center;
  private $lng_west;

  /**
   * @return mixed
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * @return mixed
   */
  public function getLatNorth() {
    return $this->lat_north;
  }

  /**
   * @return mixed
   */
  public function getLatCenter() {
    return $this->lat_center;
  }

  /**
   * @return mixed
   */
  public function getLatSouth() {
    return $this->lat_south;
  }

  /**
   * @return mixed
   */
  public function getLngEast() {
    return $this->lng_east;
  }

  /**
   * @return mixed
   */
  public function getLngCenter() {
    return $this->lng_center;
  }

  /**
   * @return mixed
   */
  public function getLngWest() {
    return $this->lng_west;
  }

  public function __construct($code, $lat_south, $lat_north, $lat_center, $lng_west, $lng_east, $lng_center) {
    $this->code = $code;
    $this->lat_south = $lat_south;
    $this->lat_north = $lat_north;
    $this->lat_center = $lat_center;
    $this->lng_west = $lng_west;
    $this->lng_east = $lng_east;
    $this->lng_center = $lng_center;
  }

}
