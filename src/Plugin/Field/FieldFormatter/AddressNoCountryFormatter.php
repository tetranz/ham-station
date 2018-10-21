<?php

namespace Drupal\ham_station\Plugin\Field\FieldFormatter;

use Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'address_no_country' formatter.
 *
 * @FieldFormatter(
 *   id = "address_no_country",
 *   label = @Translation("No country"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressNoCountryFormatter extends AddressDefaultFormatter {

  public static function postRender($content, array $element) {
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format */
    $address_format = $element['#address_format'];
    $format_string = $address_format->getFormat();

    $replacements = [];
    foreach (Element::getVisibleChildren($element) as $key) {
      $child = $element[$key];
      if (isset($child['#placeholder'])) {
        $replacements[$child['#placeholder']] = $child['#value'] ? $child['#markup'] : '';
      }
    }
    $content = self::replacePlaceholders($format_string, $replacements);
    $content = nl2br($content, FALSE);

    return $content;
  }

}
