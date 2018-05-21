<?php

/**
 * @file
 * Contains asset_entity.page.inc.
 *
 * Page callback for Asset entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Asset entity templates.
 *
 * Default template: asset_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_asset_entity(array &$variables) {
  // Fetch AssetEntity Entity Object.
  $asset_entity = $variables['elements']['#asset_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
