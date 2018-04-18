<?php

namespace Drupal\ham_station;

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
   * ReportService constructor.
   *
   * @param \Drupal\Core\Database\Connection $dbConnection
   */
  public function __construct(Connection $dbConnection) {
    $this->dbConnection = $dbConnection;
  }

  /**
   * Get the geocoding status per state.
   *
   * @return array
   *
   */
  public function geocodeStatus() {
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

    return $result;
  }

}
