<?php

namespace Drupal\ham_station\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Amateur Radio Station entities.
 */
class HamStationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

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

    $data['ham_station']['address_hash']['relationship'] = [
      'title' => t('Ham Address'),
      'help' => t('The Ham Address of this station.'),
      'base' => 'ham_address',
      'base field' => 'hash',
      'id' => 'standard',
    ];

    return $data;
  }

}
