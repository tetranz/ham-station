<?php

/**
 * Implements hook_views_data_alter().
 */
function ham_station_views_data_alter(&$data) {
  $data['ham_station']['distance'] = [
    'title' => t('Distance'),
    'help' => t('Distance from the specified point.'),
    'argument' => [
      'id' => 'ham_station_distance',
    ],
  ];

  return $data;
}
