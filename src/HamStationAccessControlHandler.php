<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Amateur Radio Station entity.
 *
 * @see \Drupal\ham_station\Entity\HamStation.
 */
class HamStationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ham_station\Entity\HamStationInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished amateur radio station entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published amateur radio station entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit amateur radio station entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete amateur radio station entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add amateur radio station entities');
  }

}
