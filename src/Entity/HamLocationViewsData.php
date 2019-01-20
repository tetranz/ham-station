<?php

namespace Drupal\ham_station\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ham location entities.
 */
class HamLocationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
