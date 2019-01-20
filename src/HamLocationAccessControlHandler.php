<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ham location entity.
 *
 * @see \Drupal\ham_station\Entity\HamLocation.
 */
class HamLocationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ham_station\Entity\HamLocationInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ham location entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published ham location entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ham location entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ham location entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ham location entities');
  }

}
