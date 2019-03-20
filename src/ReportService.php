<?php

namespace Drupal\ham_station;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;

/**
 * Provides queried data.
 */
class ReportService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $dbConnection;

  /**
   * The Cache
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * ReportService constructor.
   *
   * @param \Drupal\Core\Database\Connection $dbConnection
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   */
  public function __construct(Connection $dbConnection, CacheBackendInterface $cache) {
    $this->dbConnection = $dbConnection;
    $this->cache = $cache;
  }

  /**
   * Get the geocoding status per state.
   *
   * @return array
   *
   */
  public function geocodeStatus() {
    // Generate a geocode status report by state.
    $query = $this->dbConnection->select('ham_address', 'ha');
    $query->addField('ha', 'address__administrative_area', 'state');
    $query->addField('ha', 'geocode_status', 'status');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('ha.address__administrative_area', '', '>');
    $query->condition('ha.address__administrative_area', ['AA', 'AE'], 'NOT IN');
    $query->groupBy('ha.address__administrative_area, ha.geocode_status');
    $rows = $query->execute();

    $totals = [0, 0, 0, 0];
    $states = [];

    foreach ($rows as $row) {
      if (!isset($states[$row->state])) {
        $states[$row->state] = [0, 0, 0, 0];
      }

      $states[$row->state][$row->status] = $row->count;
      $totals[$row->status] += $row->count;
    }

    ksort($states);

    $result = [
      'states' => $states,
      'totals' => $totals,
    ];

    return $result;
  }

}
