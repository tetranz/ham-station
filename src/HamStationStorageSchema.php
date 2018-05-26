<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the ham_station schema handler.
 */
class HamStationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['ham_station']['indexes'] += [
        'ham_station_state_geocode_status' => ['address__administrative_area', 'geocode_status'],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    $columns = [
      'callsign' => TRUE,
      'latitude' => FALSE,
      'longitude' => FALSE,
      'address_hash' => TRUE,
    ];

    $field_name = $storage_definition->getName();

    if (isset($columns[$field_name])) {
      $this->addSharedTableFieldIndex($storage_definition, $schema, $columns[$field_name]);
    }

    return $schema;
  }

}
