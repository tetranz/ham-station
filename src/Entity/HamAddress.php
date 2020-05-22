<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Ham address entity.
 *
 * @ingroup ham_station
 *
 * @ContentEntityType(
 *   id = "ham_address",
 *   label = @Translation("Ham address"),
 *   handlers = {
 *     "storage_schema" = "Drupal\ham_station\HamAddressStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ham_station\HamAddressListBuilder",
 *     "views_data" = "Drupal\ham_station\Entity\HamAddressViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ham_station\Form\HamAddressForm",
 *       "add" = "Drupal\ham_station\Form\HamAddressForm",
 *       "edit" = "Drupal\ham_station\Form\HamAddressForm",
 *       "delete" = "Drupal\ham_station\Form\HamAddressDeleteForm",
 *     },
 *     "access" = "Drupal\ham_station\HamAddressAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\ham_station\HamAddressHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ham_address",
 *   admin_permission = "administer ham address entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "hash",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ham_address/{ham_address}",
 *     "add-form" = "/admin/structure/ham_address/add",
 *     "edit-form" = "/admin/structure/ham_address/{ham_address}/edit",
 *     "delete-form" = "/admin/structure/ham_address/{ham_address}/delete",
 *     "collection" = "/admin/structure/ham_address",
 *   },
 *   field_ui_base_route = "ham_address.settings"
 * )
 */
class HamAddress extends ContentEntityBase implements HamAddressInterface {

  use EntityChangedTrait;

  /**
   * Allowed values for the geocode_status field.
   */
  const GEOCODE_STATUS_PENDING = 0;
  const GEOCODE_STATUS_SUCCESS = 1;
  const GEOCODE_STATUS_NOT_FOUND = 2;

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
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
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

    $fields['hash'] = EntityHelper::stringFieldDef('Hash', 40, $weight++);

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

    $fields['geocode_provider'] = EntityHelper::stringFieldDef('Geocode provider', 2, $weight++);

    $fields['geocode_status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Geocode status'))
      ->setSetting('allowed_values', [
        static::GEOCODE_STATUS_PENDING => 'Pending',
        static::GEOCODE_STATUS_SUCCESS => 'Success',
        static::GEOCODE_STATUS_NOT_FOUND => 'Not found',
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

    $fields['geocode_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Geocode response'))
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
      ->setRequired(FALSE);

    $weight++;

    $fields['geocode_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Geocode time'))
      ->setDescription(t('Timestamp of last geocode attempt.'))
      ->setDisplayOptions('view', [
        'weight' => $weight,
      ])
      ->setDisplayOptions('form', [
        'weight' => $weight,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $weight++;

    $fields['location_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setDescription(t('The related HamLocation entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'ham_location')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['osm_geocode_status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('OSM geocode status'))
      ->setSetting('allowed_values', [
        static::GEOCODE_STATUS_PENDING => 'Pending',
        static::GEOCODE_STATUS_SUCCESS => 'Success',
        static::GEOCODE_STATUS_NOT_FOUND => 'Not found',
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

    $fields['osm_geocode_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('OSM geocode response'))
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
      ->setRequired(FALSE);

    $fields['osm_latitude'] = EntityHelper::decimalFieldDef('OSM latitude', 10, 7, $weight++);
    $fields['osm_longitude'] = EntityHelper::decimalFieldDef('OSM longitude', 10, 7, $weight++);

    $fields['geocode_priority'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Geocode priority'))
    ->setDisplayOptions('view', [
      'label' => 'above',
      'weight' => $weight,
    ])
    ->setDisplayOptions('form', [
      'weight' => $weight,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Ham address entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
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
      ->setDescription(t('A boolean indicating whether the Ham address is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
