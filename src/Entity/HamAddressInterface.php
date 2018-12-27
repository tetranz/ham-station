<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ham address entities.
 *
 * @ingroup ham_station
 */
interface HamAddressInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Ham address name.
   *
   * @return string
   *   Name of the Ham address.
   */
  public function getName();

  /**
   * Sets the Ham address name.
   *
   * @param string $name
   *   The Ham address name.
   *
   * @return \Drupal\ham_station\Entity\HamAddressInterface
   *   The called Ham address entity.
   */
  public function setName($name);

  /**
   * Gets the Ham address creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ham address.
   */
  public function getCreatedTime();

  /**
   * Sets the Ham address creation timestamp.
   *
   * @param int $timestamp
   *   The Ham address creation timestamp.
   *
   * @return \Drupal\ham_station\Entity\HamAddressInterface
   *   The called Ham address entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Ham address published status indicator.
   *
   * Unpublished Ham address are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Ham address is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Ham address.
   *
   * @param bool $published
   *   TRUE to set this Ham address to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ham_station\Entity\HamAddressInterface
   *   The called Ham address entity.
   */
  public function setPublished($published);

}
