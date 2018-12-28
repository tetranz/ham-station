<?php

namespace Drupal\ham_station\Plugin\views\field;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ham_station\DistanceService;
use Drupal\views\Plugin\views\field\NumericField;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("ham_station_distance")
 */
class Distance extends NumericField implements ContainerFactoryPluginInterface {

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
  public function query() {
    $this->ensureMyTable();

    /** @var \Drupal\ham_station\Plugin\views\argument\ArgInterface $argument */
    $argument = reset($this->view->argument);
    $values = $argument->getParsedArgument();
    $formula = $this->distanceService->getDistanceFormula($values['lat'], $values['lng'], $values['units'], $this->table);
    $this->field_alias = $this->query->addField(NULL, $formula, $this->tableAlias . '_' . $this->field);
  }

}
