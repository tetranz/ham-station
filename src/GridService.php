<?php

namespace Drupal\ham_station;

/*
 * Class to provide Maidenhead grid square calculations.
 */
class GridService {
  /**
   * @param float $lat
   *   Latitude.
   * @param float $lng
   *   Longitude
   * @return null|string
   *   6 character subsquare code.
   */
  public function calculateGridSquare($lat, $lng) {

    if (abs($lat) >= 90 || abs($lng) >= 180) {
      return NULL;
    }

    $lat += 90;
    $lng += 180;

    $upperA = ord('A');
    $lowerA = ord('a');
    $zero = ord('0');

    $locator = [];

    $locator[] = chr($upperA + ($lng / 20));
    $locator[] = chr($upperA + ($lat / 10));

    $locator[] = chr($zero + (($lng % 20) / 2));
    $locator[] = chr($zero + ($lat % 10));

    $locator[] = chr($lowerA + fmod($lng, 2) * 12);
    $locator[] = chr($lowerA + fmod($lat, 1) * 24);

    return implode('', $locator);
  }

}
