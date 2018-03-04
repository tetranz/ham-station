<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Amateur Radio Station entities.
 *
 * @ingroup ham_station
 */
class HamStationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Amateur Radio Station ID');
    $header['callsign'] = $this->t('Callsign');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ham_station\Entity\HamStation */
    $row['id'] = $entity->id();
    $row['callsign'] = Link::createFromRoute(
      $entity->label(),
      'entity.ham_station.edit_form',
      ['ham_station' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
