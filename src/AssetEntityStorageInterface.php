<?php

namespace Drupal\service_club_asset;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\service_club_asset\Entity\AssetEntityInterface;

/**
 * Defines the storage handler class for Asset entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Asset entity entities.
 *
 * @ingroup service_club_asset
 */
interface AssetEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Asset entity revision IDs for a specific Asset entity.
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntityInterface $entity
   *   The Asset entity entity.
   *
   * @return int[]
   *   Asset entity revision IDs (in ascending order).
   */
  public function revisionIds(AssetEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Asset entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Asset entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntityInterface $entity
   *   The Asset entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(AssetEntityInterface $entity);

  /**
   * Unsets the language for all Asset entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
