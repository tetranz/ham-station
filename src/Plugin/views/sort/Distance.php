<?php

namespace Drupal\ham_station\Plugin\views\sort;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ham_station\DistanceService;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("ham_station_distance")
 */
class Distance extends SortPluginBase implements ContainerFactoryPluginInterface {

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
    $this->query->addOrderBy(NULL, NULL, $this->options['order'], $this->tableAlias . '_distance');
  }
  
}
