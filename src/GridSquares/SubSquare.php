<?php

namespace Drupal\ham_station\GridSquares;

/**
 * Represents a grid subsquare.
 */
class SubSquare {

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

  public function __construct($code) {
    $this->code = $code;
    $this->calculate();
  }

  private function calculate() {
    $upper_a = ord('A');
    $zero = ord('0');

    $code = strtoupper($this->code);

    $lng = (ord($code[0]) - $upper_a) * 20;
    $lat = (ord($code[1]) - $upper_a) * 10;

    $lng += (ord($code[2]) - $zero) * 2;
    $lat += (ord($code[3]) - $zero);

    $lng += (ord($code[4]) - $upper_a) / 12;
    $lat += (ord($code[5]) - $upper_a) / 24;

    $this->lng_west = $lng - 180;
    $this->lng_east = $this->lng_west + (1/12);
    $this->lng_center = ($this->lng_east + $this->lng_west) / 2;

    $this->lat_south = $lat - 90;
    $this->lat_north = $this->lat_south + (1/24);
    $this->lat_center = ($this->lat_north + $this->lat_south) / 2;
  }

}
