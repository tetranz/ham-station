<?php

namespace Drupal\ham_station\GridSquares;

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
   * Cache of neighboring subsquares keyed by central code.
   *
   * @var array
   */
  private $neighboringSubsquares = [];

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
   * @param flow $lng
   *   Longatude.
   * @return SubSquare
   *   Subsquare.
   */
  public function createSubsquareFromLatLng($lat, $lng) {
    $code = $this->latLngToSubsquareCode($lat, $lng);
    return $this->createSubsquareFromCode($code);
  }

  /**
   * Get a subsquare's neighboring subsquares.
   *
   * @param Subsquare $subsquare
   *   Central subsquare.
   *
   * @return array
   *   Array of subsquares.
   */
  public function getNeighboringSubsquares(Subsquare $subsquare) {
    $code_uc = strtoupper($subsquare->getCode());
    
    if (isset($this->neighboringSubsquares[$code_uc])) {
      return $this->neighboringSubsquares[$code_uc];
    }
    
    $delta = 0.01;
    $neighbors = [
      'c' => $subsquare,
      'nw' => $this->createSubsquareFromLatLng($subsquare->getLatNorth() + $delta, $subsquare->getLngWest() - $delta),
      'n' => $this->createSubsquareFromLatLng($subsquare->getLatNorth() + $delta, $subsquare->getLngCenter()),
      'ne' => $this->createSubsquareFromLatLng($subsquare->getLatNorth() + $delta, $subsquare->getLngEast() + $delta),
      'e' => $this->createSubsquareFromLatLng($subsquare->getLatCenter(), $subsquare->getLngEast() + $delta),
      'se' => $this->createSubsquareFromLatLng($subsquare->getLatSouth() - $delta, $subsquare->getLngEast() + $delta),
      's' => $this->createSubsquareFromLatLng($subsquare->getLatSouth() - $delta, $subsquare->getLngCenter()),
      'sw' => $this->createSubsquareFromLatLng($subsquare->getLatSouth() - $delta, $subsquare->getLngWest() - $delta),
      'w' => $this->createSubsquareFromLatLng($subsquare->getLatCenter(), $subsquare->getLngWest() - $delta),
    ];
    
    $this->neighboringSubsquares[$code_uc] = $neighbors;
    return $neighbors;
  }
  
}
