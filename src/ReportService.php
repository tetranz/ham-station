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

    $cache_key = 'ham_station_geocode_counts';
    $result = $this->cache->get($cache_key);

    if ($result !== FALSE) {
      return $result->data;
    }

    // Generate a geocode status report by state.
    $query = $this->dbConnection->select('ham_station', 'hs');
    $query->addField('hs', 'address__administrative_area', 'state');
    $query->addField('hs', 'geocode_status', 'status');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('address__administrative_area', '', '>');
    $query->groupBy('hs.address__administrative_area, hs.geocode_status');
    $rows = $query->execute();

    $totals = [0, 0, 0];
    $states = [];

    foreach ($rows as $row) {
      if (!isset($states[$row->state])) {
        $states[$row->state] = [0, 0, 0];
      }

      $states[$row->state][$row->status] = $row->count;
      $totals[$row->status] += $row->count;
    }

    ksort($states);

    $result = [
      'states' => $states,
      'totals' => $totals,
    ];

    $done = [];
    $working_on = NULL;

    foreach ($states as $state => $counts) {
      if ($counts[0] == 0) {
        $done[] = $state;
      }
      elseif ($working_on === NULL && ($counts[1] > 0 || $counts[2] > 0)) {
        $working_on = $state;
      }
    }

    $result['done'] = $done;
    $result['working_on'] = $working_on;

    // Cache is invalidated when geocoding happens.
    $this->cache->set($cache_key, $result);

    return $result;
  }

}
