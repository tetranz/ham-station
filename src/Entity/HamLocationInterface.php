<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ham location entities.
 *
 * @ingroup ham_station
 */
interface HamLocationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Ham location creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ham location.
   */
  public function getCreatedTime();

  /**
   * Sets the Ham location creation timestamp.
   *
   * @param int $timestamp
   *   The Ham location creation timestamp.
   *
   * @return \Drupal\ham_station\Entity\HamLocationInterface
   *   The called Ham location entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Ham location published status indicator.
   *
   * Unpublished Ham location are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Ham location is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Ham location.
   *
   * @param bool $published
   *   TRUE to set this Ham location to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ham_station\Entity\HamLocationInterface
   *   The called Ham location entity.
   */
  public function setPublished($published);

}
