<?php

namespace Drupal\service_club_asset\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Asset entity entities.
 */
class AssetEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
