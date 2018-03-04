<?php

/**
 * @file
 * Contains ham_station.page.inc.
 *
 * Page callback for Amateur Radio Station entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Amateur Radio Station templates.
 *
 * Default template: ham_station.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ham_station(array &$variables) {
  // Fetch HamStation Entity Object.
  $ham_station = $variables['elements']['#ham_station'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
