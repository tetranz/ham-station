<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ham address entity.
 *
 * @see \Drupal\ham_station\Entity\HamAddress.
 */
class HamAddressAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ham_station\Entity\HamAddressInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ham address entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published ham address entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ham address entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ham address entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ham address entities');
  }

}
