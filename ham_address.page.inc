<?php

/**
 * @file
 * Contains ham_address.page.inc.
 *
 * Page callback for Ham address entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Ham address templates.
 *
 * Default template: ham_address.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ham_address(array &$variables) {
  // Fetch HamAddress Entity Object.
  $ham_address = $variables['elements']['#ham_address'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
