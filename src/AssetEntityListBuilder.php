<?php

namespace Drupal\service_club_asset;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Asset entity entities.
 *
 * @ingroup service_club_asset
 */
class AssetEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Asset entity ID');
    $header['name'] = $this->t('Name');
    $header['expiry_date'] = $this->t('Expiry Date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\service_club_asset\Entity\AssetEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.asset_entity.edit_form',
      ['asset_entity' => $entity->id()]
    );
    $row['expiry_date'] = $entity->getExpiryDate();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    if ($entity->access('clone') && $entity->hasLinkTemplate('clone-asset')) {
      $operations['clone'] = [
        'title' => $this->t('Clone'),
        'weight' => 1000,
        'url' => $this->ensureDestination($entity->toUrl('clone-asset')),
      ];
    }

    return $operations + parent::getDefaultOperations($entity);
  }

}
