<?php

namespace Drupal\service_club_asset\Plugin\QueueWorker;

use Drupal\service_club_asset\Entity\AssetEntity;
use Drupal\Component\Datetime;

/**
 * Processes Assets and checks if they have expired.
 *
 * @QueueWorker(
 *   id = "asset_entity_processor",
 *   title = @Translation("Task Worker"),
 *   cron = {"time" = 150}
 * )
 */
class AssetEntityProcessor extends AssetEntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data)) {

      // Guardian IF to ensure the $data is an asset.
      if ($data instanceof AssetEntity) {

        // Get the current time and convert to stored date format.
        $current_time = \Drupal::time()->getCurrentTime();
        $current_date = date("Y-m-d", $current_time);

        // Compare the asset date to the current date.
        if ($data->getExpiryDate() < $current_date) {
          // Create a string for message and concatenate with relevant data.
          $message = 'The asset: ' . $data->getName() . '. id: ' . $data->id() . ' has expired.';
          $this->logger->get('Asset Entity processItem')
            ->notice($message);
        }
        // Otherwise it hasn't expired.
      }
    }
  }

}
