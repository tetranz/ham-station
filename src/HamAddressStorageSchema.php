<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

class HamAddressStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($base_table = $this->storage->getBaseTable()) {
      $schema[$base_table]['unique keys'] += [
        'ham_address_field__hash' => ['hash'],
      ];
    }

    return $schema;
  }

}
