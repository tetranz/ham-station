<?php

namespace Drupal\ham_station\GridSquares;

/*
 * Class to provide Maidenhead grid square calculations.
 */
class Calculator {
  /**
   * @param float $lat
   *   Latitude.
   * @param float $lng
   *   Longitude
   * @return null|string
   *   6 character subsquare code.
   */
  public function latLngToSubSquare($lat, $lng) {

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

}
