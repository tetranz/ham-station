<?php

namespace Drupal\ham_station\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Amateur Radio Station entities.
 *
 * @ingroup ham_station
 */
interface HamStationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Amateur Radio Station name.
   *
   * @return string
   *   Callsign of the Amateur Radio Station.
   */
  public function getCallsign();

  /**
   * Sets the Amateur Radio Station callsign.
   *
   * @param string $name
   *   The Amateur Radio Station name.
   *
   * @return \Drupal\ham_station\Entity\HamStationInterface
   *   The called Amateur Radio Station entity.
   */
  public function setCallsign($callsign);

  /**
   * Gets the Amateur Radio Station creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Amateur Radio Station.
   */
  public function getCreatedTime();

  /**
   * Sets the Amateur Radio Station creation timestamp.
   *
   * @param int $timestamp
   *   The Amateur Radio Station creation timestamp.
   *
   * @return \Drupal\ham_station\Entity\HamStationInterface
   *   The called Amateur Radio Station entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Amateur Radio Station published status indicator.
   *
   * Unpublished Amateur Radio Station are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Amateur Radio Station is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Amateur Radio Station.
   *
   * @param bool $published
   *   TRUE to set this Amateur Radio Station to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\ham_station\Entity\HamStationInterface
   *   The called Amateur Radio Station entity.
   */
  public function setPublished($published);

}
