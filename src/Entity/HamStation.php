<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ham_station\Geocoder;
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
    $fields['callsign'] = EntityHelper::stringFieldDef('Callsign', 20, $weight++);
    $fields['first_name'] = EntityHelper::stringFieldDef('First Name', 255, $weight++);
    $fields['middle_name'] = EntityHelper::stringFieldDef('Middle Initial or Name', 255, $weight++);
    $fields['last_name'] = EntityHelper::stringFieldDef('Last Name', 255, $weight++);
    $fields['suffix'] = EntityHelper::stringFieldDef('Suffix', 3, $weight++);
    $fields['organization'] = EntityHelper::stringFieldDef('Organization Name', 255, $weight++);
    $fields['operator_class'] = EntityHelper::stringFieldDef('Operator class', 1, $weight++);
    $fields['previous_callsign'] = EntityHelper::stringFieldDef('Previous callsign', 20, $weight++);

    $fields['total_hash'] = EntityHelper::stringFieldDef('Total hash', 40, $weight++);
    $fields['address_hash'] = EntityHelper::stringFieldDef('Address hash', 40, $weight);

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

}
