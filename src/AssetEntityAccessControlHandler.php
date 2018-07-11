<?php

namespace Drupal\service_club_asset;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Asset entity entity.
 *
 * @see \Drupal\service_club_asset\Entity\AssetEntity.
 */
class AssetEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\service_club_asset\Entity\AssetEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished asset entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published asset entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit asset entity entities');

      case 'clone':
        return AccessResult::allowedIfHasPermission($account, 'clone asset entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete asset entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add asset entity entities');
  }

}
