<?php

namespace Drupal\ham_station\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Formula;
use Drupal\views\Plugin\views\query\Sql;

/**
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("ham_station_distance")
 */
class Distance extends Formula {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Use the haversine formula to compute distance.
    // See https://www.plumislandmedia.net/mysql/haversine-mysql-nearest-loc/
    $parts = explode('|', $this->getValue());
    $lat = floatval($parts[0]);
    $lng = floatval($parts[1]);
    $radius = $parts[2];
    $distance_unit = strpos($parts[3], 'mile') !== FALSE ? 69.0 : 111.045;
    $lat_rad = deg2rad($lat);
    $cos_lat_rad = cos($lat_rad);

    $lat_delta = $radius / $distance_unit;
    $lat_from = $lat - $lat_delta;
    $lat_to = $lat + $lat_delta;

    $lng_delta = $radius / ($distance_unit * $cos_lat_rad);
    $lng_from = $lng - $lng_delta;
    $lng_to = $lng + $lng_delta;

    $formula = sprintf('latitude BETWEEN %s AND %s', $lat_from, $lat_to);
    $formula .= sprintf(' AND longitude BETWEEN %s AND %s', $lng_from, $lng_to);

    $tmp1 = $cos_lat_rad;
    $tmp2 = sprintf('COS(RADIANS(%s.latitude))', $this->tableAlias);
    $tmp3 = sprintf('COS(RADIANS(%s - %s.longitude))', $lng, $this->tableAlias);
    $tmp4 = sin($lat_rad);
    $tmp5 = sprintf('SIN(RADIANS(%s.latitude))', $this->tableAlias);

    $formula .= sprintf(' AND (%f * DEGREES(ACOS(%s * %s * %s + (%s * %s)))) < %f', $distance_unit, $tmp1, $tmp2, $tmp3, $tmp4, $tmp5, $radius);
    $this->query->addWhere(0, $formula, [], 'formula');
  }
}
