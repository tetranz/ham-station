<?php

/**
 * @file
 * Contains ham_location.page.inc.
 *
 * Page callback for Ham location entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Ham location templates.
 *
 * Default template: ham_location.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ham_location(array &$variables) {
  // Fetch HamLocation Entity Object.
  $ham_location = $variables['elements']['#ham_location'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
