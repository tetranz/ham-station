<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the ham_station schema handler.
 */
class HamStationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    switch ($field_name) {
      case 'callsign':
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
        break;

      case 'address_hash':
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
        break;
    }

    return $schema;
  }

}
