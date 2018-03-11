<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Amateur Radio Station entity.
 *
 * @ingroup ham_station
 *
 * @ContentEntityType(
 *   id = "ham_station",
 *   label = @Translation("Amateur Radio Station"),
 *   handlers = {
 *     "storage_schema" = "Drupal\ham_station\HamStationStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ham_station\HamStationListBuilder",
 *     "views_data" = "Drupal\ham_station\Entity\HamStationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ham_station\Form\HamStationForm",
 *       "add" = "Drupal\ham_station\Form\HamStationForm",
 *       "edit" = "Drupal\ham_station\Form\HamStationForm",
 *       "delete" = "Drupal\ham_station\Form\HamStationDeleteForm",
 *     },
 *     "access" = "Drupal\ham_station\HamStationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\ham_station\HamStationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ham_station",
 *   admin_permission = "administer amateur radio station entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "callsign",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ham_station/{ham_station}",
 *     "add-form" = "/admin/structure/ham_station/add",
 *     "edit-form" = "/admin/structure/ham_station/{ham_station}/edit",
 *     "delete-form" = "/admin/structure/ham_station/{ham_station}/delete",
 *     "collection" = "/admin/structure/ham_station",
 *   },
 *   field_ui_base_route = "ham_station.settings"
 * )
 */
class HamStation extends ContentEntityBase implements HamStationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCallsign() {
    return $this->get('callsign')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCallsign($callsign) {
    $this->set('callsign', $callsign);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $weight = 0;
    $fields['callsign'] = static::stringFieldDef('Callsign', 20, $weight++);
    $fields['first_name'] = static::stringFieldDef('First Name', 255, $weight++);
    $fields['middle_name'] = static::stringFieldDef('Middle Initial or Name', 255, $weight++);
    $fields['last_name'] = static::stringFieldDef('Last Name', 255, $weight++);
    $fields['suffix'] = static::stringFieldDef('Suffix', 3, $weight++);
    $fields['organization'] = static::stringFieldDef('Organization Name', 255, $weight++);

    // Use CommerceGuys Address field for address.
    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setSetting('fields', [
        'administrativeArea' => 'administrativeArea',
        'locality' => 'locality',
        'dependentLocality' => 'dependentLocality',
        'postalCode' => 'postalCode',
        'sortingCode' => 'sortingCode',
        'addressLine1' => 'addressLine1',
        'addressLine2' => 'addressLine2',
        'organization' => '0',
        'givenName' => '0',
        'additionalName' => '0',
        'familyName' => '0',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'weight' => $weight,
        'settings' => [
          'default_country' => 'US',
        ],
      ]);

    $weight++;
    $fields['operator_class'] = static::stringFieldDef('Operator class', 1, $weight++);
    $fields['previous_callsign'] = static::stringFieldDef('Previous callsign', 20, $weight++);

    $fields['address_type'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Address type'))
      ->setSetting('allowed_values', [
        0 => 'Undetermined',
        1 => 'Physical',
        2 => 'Non-physical (PO Box etc)',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['geolocation_status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Geolocation status'))
      ->setSetting('allowed_values', [
        0 => 'Pending',
        1 => 'Success',
        2 => 'Fail',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $weight++;
    $fields['total_hash'] = static::stringFieldDef('Total hash', 40, $weight++);
    $fields['address_hash'] = static::stringFieldDef('Address hash', 40, $weight++);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Amateur Radio Station entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 45,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 45,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Amateur Radio Station is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 46,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Helper to add a string field.
   */
  private static function stringFieldDef($label, $max_length, $weight, $required = FALSE) {
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
}
