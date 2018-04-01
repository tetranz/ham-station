<?php

namespace Drupal\ham_station;

/**
 * A class to generate some MySQL forumlas
 */
class DistanceService {

  const DISTANCE_PER_DEG_MILES = 69.0;
  const DISTANCE_PER_DEG_KM = 111.045;

  /**
   * Get the formula for distance between two points.
   *
   * @param float $lat
   *   Latitude.
   * @param float $lng
   *   Longitude.
   * @param string $units
   *   Units miles or km.
   * @param string $table_alias
   *   The table alias.
   *
   * @return string
   *   MySQL formula.
   */
  public function getDistanceFormula($lat, $lng, $units, $table_alias) {

    // See https://www.plumislandmedia.net/mysql/haversine-mysql-nearest-loc/
    // and https://www.plumislandmedia.net/mysql/vicenty-great-circle-distance-formula/

    // We use the Vincenty formula.
    // This is tedious to reformat so let's start with the blog code that works.

    $formula = <<<FORMULA
distance_unit *
DEGREES(
    ATAN2(
      SQRT(
        POW(COS(RADIANS(lat2)) * SIN(RADIANS(lng2 - lng1)), 2) +
        POW(COS(RADIANS(lat1)) * SIN(RADIANS(lat2)) -
             (SIN(RADIANS(lat1)) * COS(RADIANS(lat2)) *
              COS(RADIANS(lng2 - lng1))), 2)),
      SIN(RADIANS(lat1)) * SIN(RADIANS(lat2)) +
      COS(RADIANS(lat1)) * COS(RADIANS(lat2)) * COS(RADIANS(lng2 - lng1))))
FORMULA;

    // lat1, lng1 is the point we get from the argument. i.e., the center point.
    // Some of these can be calculated here and inserted into the SQL as numbers.

    $lat_rad = deg2rad($lat);
    $formula = str_replace('distance_unit', $this->getDistanceUnit($units), $formula);
    $formula = str_replace('COS(RADIANS(lat1))', cos($lat_rad), $formula);
    $formula = str_replace('SIN(RADIANS(lat1))', sin($lat_rad), $formula);
    $formula = str_replace('lng1', $lng, $formula);
    $formula = str_replace('lat2', sprintf('%s.latitude', $table_alias), $formula);
    $formula = str_replace('lng2', sprintf('%s.longitude', $table_alias), $formula);

    return $formula;
  }

  /**
   * Get the formula for the bounding box.
   *
   * @param float $lat
   *   Latitude.
   * @param float $lng
   *   Longitude.
   * @param float $radius
   *   The radius of the circle.
   * @param string $units
   *   Units miles or km.
   * @param string $table_alias
   *   The table alias.

   * @return string
   *  MySQL formula for bounding box.
   */
  public function getBoundingBoxFormula($lat, $lng, $radius, $units, $table_alias) {
    
    // The bounding box defines the range of latitude and longitude which we can
    // filter on first before applying the Vincenty formula. latitude and
    // longitude are indexed columns so this is a big performance boost.   
    $distance_unit = $this->getDistanceUnit($units);
    $lat_rad = deg2rad($lat);
    $cos_lat_rad = cos($lat_rad);

    $lat_delta = $radius / $distance_unit;
    $lat_from = $lat - $lat_delta;
    $lat_to = $lat + $lat_delta;

    $lng_delta = $radius / ($distance_unit * $cos_lat_rad);
    $lng_from = $lng - $lng_delta;
    $lng_to = $lng + $lng_delta;

    $formula = sprintf('%s.latitude BETWEEN %s AND %s', $table_alias, $lat_from, $lat_to);
    $formula .= sprintf(' AND %s.longitude BETWEEN %s AND %s', $table_alias, $lng_from, $lng_to);

    return $formula;
  }

  /**
   * Get the distance per degree of arc.
   *
   * @param string $units
   *   Units. miles or km.
   *
   * @return float
   *   The distance per degree of arc.
   */
  private function getDistanceUnit($units) {
    return strpos($units, 'mile') !== FALSE
      ? self::DISTANCE_PER_DEG_MILES
      : self::DISTANCE_PER_DEG_KM;
  }

}
