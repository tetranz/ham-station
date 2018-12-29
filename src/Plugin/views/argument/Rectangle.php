<?php

namespace Drupal\ham_station\Plugin\views\argument;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ham_station\DistanceService;
use Drupal\views\Plugin\views\argument\Formula;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("ham_station_rectangle")
 */
class Rectangle extends Formula implements ArgInterface {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $arg_values = $this->getParsedArgument();

    $formula = sprintf('%s.latitude BETWEEN %s AND %s AND %s.longitude BETWEEN %s AND %s',
      $this->tableAlias,
      $arg_values['lat_min'],
      $arg_values['lat_max'],
      $this->tableAlias,
      $arg_values['lng_min'],
      $arg_values['lng_max']
    );

    $this->query->addWhere(0, $formula, [], 'formula');
  }

  /**
   * Parse the pipe delimited argument.
   *
   * @return array
   */
  public function getParsedArgument() {
    $parts = explode('|', $this->getValue());
    
    $args = [
      'lat_min' => floatval($parts[0]),
      'lng_min' => floatval($parts[1]),
      'lat_max' => floatval($parts[2]),
      'lng_max' => floatval($parts[3]),
      'units' => $parts[4],
    ];
    
    // Center coords are used by the distance field. These are used by the
    // distance field plugin.
    $args['lat'] = ($args['lat_min'] + $args['lat_max']) / 2;
    $args['lng'] = ($args['lng_min'] + $args['lng_max']) / 2;

    return $args;
  }

}
