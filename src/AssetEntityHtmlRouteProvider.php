<?php

namespace Drupal\service_club_asset;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Asset entity entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class AssetEntityHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($history_route = $this->getHistoryRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.version_history", $history_route);
    }

    if ($revision_route = $this->getRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision", $revision_route);
    }

    if ($revert_route = $this->getRevisionRevertRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_revert", $revert_route);
    }

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_delete", $delete_route);
    }

    if ($translation_route = $this->getRevisionTranslationRevertRoute($entity_type)) {
      $collection->add("{$entity_type_id}.revision_revert_translation_confirm", $translation_route);
    }

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    if ($clone_asset_route = $this->getCloneAssetRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.clone_asset", $clone_asset_route);
    }

    if ($related_assets_route = $this->getAssetRelationshipRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.related_assets", $related_assets_route);
    }

    return $collection;
  }

  /**
   * Gets the clone asset route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return Symfony\Component\Routing\Route
   *   The generated route, if available.
   */
  protected function getCloneAssetRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('clone-asset')) {
      $route = new Route($entity_type->getLinkTemplate('clone-asset'));
      $route
        ->setDefaults([
          '_title' => "Clone Asset",
          '_form' => '\Drupal\service_club_asset\Form\CloneAssetForm',
        ])
        ->setRequirement('_permission', 'clone asset')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the asset relationship route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return Symfony\Component\Routing\Route
   *   The generated route, if available.
   */
  protected function getAssetRelationshipRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('related-assets')) {
      $route = new Route($entity_type->getLinkTemplate('related-assets'));
      $route
        ->setDefaults([
          '_title' => "Related Assets",
          '_form' => '\Drupal\service_club_asset\Form\AssetRelationship',
        ])
        ->setRequirement('_permission', 'related assets')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the version history route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getHistoryRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('version-history')) {
      $route = new Route($entity_type->getLinkTemplate('version-history'));
      $route
        ->setDefaults([
          '_title' => "{$entity_type->getLabel()} revisions",
          '_controller' => '\Drupal\service_club_asset\Controller\AssetEntityController::revisionOverview',
        ])
        ->setRequirement('_permission', 'access asset entity revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision')) {
      $route = new Route($entity_type->getLinkTemplate('revision'));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\service_club_asset\Controller\AssetEntityController::revisionShow',
          '_title_callback' => '\Drupal\service_club_asset\Controller\AssetEntityController::revisionPageTitle',
        ])
        ->setRequirement('_permission', 'access asset entity revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the revision revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRevertRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_revert')) {
      $route = new Route($entity_type->getLinkTemplate('revision_revert'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\service_club_asset\Form\AssetEntityRevisionRevertForm',
          '_title' => 'Revert to earlier revision',
        ])
        ->setRequirement('_permission', 'revert all asset entity revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the revision delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionDeleteRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision_delete')) {
      $route = new Route($entity_type->getLinkTemplate('revision_delete'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\service_club_asset\Form\AssetEntityRevisionDeleteForm',
          '_title' => 'Delete earlier revision',
        ])
        ->setRequirement('_permission', 'delete all asset entity revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the revision translation revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionTranslationRevertRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('translation_revert')) {
      $route = new Route($entity_type->getLinkTemplate('translation_revert'));
      $route
        ->setDefaults([
          '_form' => '\Drupal\service_club_asset\Form\AssetEntityRevisionRevertTranslationForm',
          '_title' => 'Revert to earlier revision of a translation',
        ])
        ->setRequirement('_permission', 'revert all asset entity revisions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/structure/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => 'Drupal\service_club_asset\Form\AssetEntitySettingsForm',
          '_title' => "{$entity_type->getLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

}
