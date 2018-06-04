<?php

namespace Drupal\ham_station\Plugin\views\argument;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ham_station\DistanceService;
use Drupal\views\Plugin\views\argument\Formula;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("ham_station_distance")
 */
class Distance extends Formula implements ContainerFactoryPluginInterface, ArgInterface {

  /**
   * The distance service.
   *
   * @var \Drupal\ham_station\DistanceService
   */
  private $distanceService;
  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ham_station\DistanceService $distance_service
   *   The distance service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DistanceService $distance_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->distanceService = $distance_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var DistanceService $distance_service */
    $distance_service = $container->get('ham_station.distance_service');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $distance_service
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $arg_values = $this->getParsedArgument();
    $lat = $arg_values['lat'];
    $lng = $arg_values['lng'];
    $radius = $arg_values['radius'];
    $units = $arg_values['units'];

    $formula = sprintf('%s AND (%s < %F)',
      $this->distanceService->getBoundingBoxFormula($lat, $lng, $radius, $units, $this->tableAlias),
      $this->distanceService->getDistanceFormula($lat, $lng, $units, $this->tableAlias),
      $radius
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
    
    return [
      'lat' => floatval($parts[0]),
      'lng' => floatval($parts[1]),
      'radius' => $parts[2],
      'units' => $parts[3],
    ];
  }

}
