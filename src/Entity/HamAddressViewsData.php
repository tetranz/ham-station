<?php

namespace Drupal\ham_station\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ham address entities.
 */
class HamAddressViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['ham_address']['distance'] = [
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

    $data['ham_address']['rectangle'] = [
      'title' => t('Rectangle'),
      'help' => t('A rectangle defined by latitude and longitude.'),
      'argument' => [
        'id' => 'ham_station_rectangle',
      ],
    ];

    $data['ham_address']['hash']['relationship'] = [
      'title' => t('Ham stations'),
      'help' => t('The Ham Stations at this address.'),
      'base' => 'ham_station',
      'base field' => 'address_hash',
      'id' => 'standard',
    ];

    return $data;
  }

}
