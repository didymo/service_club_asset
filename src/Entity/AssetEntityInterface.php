<?php

namespace Drupal\service_club_asset\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Asset entity entities.
 *
 * @ingroup service_club_asset
 */
interface AssetEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Asset entity name.
   *
   * @return string
   *   Name of the Asset entity.
   */
  public function getName();

  /**
   * Sets the Asset entity name.
   *
   * @param string $name
   *   The Asset entity name.
   *
   * @return \Drupal\service_club_asset\Entity\AssetEntityInterface
   *   The called Asset entity entity.
   */
  public function setName($name);

  /**
   * Gets the Asset entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Asset entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Asset entity creation timestamp.
   *
   * @param int $timestamp
   *   The Asset entity creation timestamp.
   *
   * @return \Drupal\service_club_asset\Entity\AssetEntityInterface
   *   The called Asset entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Asset entity published status indicator.
   *
   * Unpublished Asset entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Asset entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Asset entity.
   *
   * @param bool $published
   *   TRUE to set this Asset entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\service_club_asset\Entity\AssetEntityInterface
   *   The called Asset entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Asset entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Asset entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\service_club_asset\Entity\AssetEntityInterface
   *   The called Asset entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Asset entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Asset entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\service_club_asset\Entity\AssetEntityInterface
   *   The called Asset entity entity.
   */
  public function setRevisionUserId($uid);

}
