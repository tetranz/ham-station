<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Field\BaseFieldDefinition;

class EntityHelper {

  /**
   * Helper to add a string field.
   */
  public static function stringFieldDef($label, $max_length, $weight, $required = FALSE) {
    return BaseFieldDefinition::create('string')
      ->setLabel(t($label))
      ->setSettings([
        'max_length' => $max_length,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired($required);
  }

  /**
   * Helper to add latitude and longitude fields.
   */
  public static function decimalFieldDef($label, $precision, $scale, $weight) {
    return BaseFieldDefinition::create('decimal')
      ->setLabel(t($label))
      ->setSettings([
        'precision' => $precision,
        'scale' => $scale,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);
  }

}