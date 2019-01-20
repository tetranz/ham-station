<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Ham location entity.
 *
 * @ingroup ham_station
 *
 * @ContentEntityType(
 *   id = "ham_location",
 *   label = @Translation("Ham location"),
 *   handlers = {
 *     "storage_schema" = "Drupal\ham_station\HamLocationStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ham_station\HamLocationListBuilder",
 *     "views_data" = "Drupal\ham_station\Entity\HamLocationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ham_station\Form\HamLocationForm",
 *       "add" = "Drupal\ham_station\Form\HamLocationForm",
 *       "edit" = "Drupal\ham_station\Form\HamLocationForm",
 *       "delete" = "Drupal\ham_station\Form\HamLocationDeleteForm",
 *     },
 *     "access" = "Drupal\ham_station\HamLocationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\ham_station\HamLocationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ham_location",
 *   admin_permission = "administer ham location entities",
 *   label_callback = "ham_station_location_label_callback",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ham_location/{ham_location}",
 *     "add-form" = "/admin/structure/ham_location/add",
 *     "edit-form" = "/admin/structure/ham_location/{ham_location}/edit",
 *     "delete-form" = "/admin/structure/ham_location/{ham_location}/delete",
 *     "collection" = "/admin/structure/ham_location",
 *   },
 *   field_ui_base_route = "ham_location.settings"
 * )
 */
class HamLocation extends ContentEntityBase implements HamLocationInterface {

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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Ham location entity.'))
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

    $fields['latitude'] = EntityHelper::decimalFieldDef('Latitude', 10, 7, -4);
    $fields['longitude'] = EntityHelper::decimalFieldDef('Longitude', 10, 7, -4);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Ham location is published.'))
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
