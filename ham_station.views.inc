<?php

/**
 * Implements hook_views_data_alter().
 */
function ham_station_views_data_alter(&$data) {
  $data['ham_station']['distance'] = [
    'title' => t('Distance'),
    'help' => t('Distance from the specified point.'),
    'field' => [
      'id' => 'ham_station_distance',
      'float' => TRUE,
    ],
    'argument' => [
      'id' => 'ham_station_distance',
    ],
    'sort' => [
      'id' => 'ham_station_distance',
    ],
  ];

  $data['ham_station']['rectangle'] = [
    'title' => t('Rectangle'),
    'help' => t('A rectangle defined by latitude and longitude.'),
    'argument' => [
      'id' => 'ham_station_rectangle',
    ],
  ];

  return $data;
}
