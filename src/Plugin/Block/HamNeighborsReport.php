<?php

namespace Drupal\ham_station\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ham_station\Form\HamNeighborsForm;
use Drupal\ham_station\ReportService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Ham map report block.
 *
 * @Block(
 *  id = "ham_map_report",
 *  admin_label = @Translation("Ham map report"),
 * )
 */
class HamNeighborsReport extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The report service.
   *
   * @var \Drupal\ham_station\ReportService
   */
  private $reportService;

  /**
   * Constructs a new AjaxFormBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ham_station\ReportService $report_service
   *   The report service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ReportService $report_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->reportService = $report_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\ham_station\ReportService $reportService */
    $reportService = $container->get('ham_station.report_service');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $reportService
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Report content is requested via ajax so we can use the anonymous page cache.
    return [
      '#markup' => '<div class="ham-map-report"></div>',
      '#attached' => [
        'library' => ['ham_station/map_report'],
        'drupalSettings' => [
          'ham_neighbors' => [
            'status' => 0,
          ],
        ],
      ],
    ];
  }

}
