<?php

namespace Drupal\ham_station\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ham_station\Form\HamNeighborsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a HamNeighborsReport block.
 *
 * @Block(
 *  id = "ham_neighbors_report",
 *  admin_label = @Translation("Ham neighbors report"),
 * )
 */
class HamNeighborsReport extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $dbConnection;

  /**
   * Constructs a new AjaxFormBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $db_connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dbConnection = $db_connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Generate a geocode status report by state.
    $query = $this->dbConnection->select('ham_station', 'hs');
    $query->addField('hs', 'address__administrative_area', 'state');
    $query->addField('hs', 'geocode_status', 'status');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('address__administrative_area', '', '>');
    $query->groupBy('hs.address__administrative_area, hs.geocode_status');
    $rows = $query->execute();

    $totals = [0, 0, 0];
    $data = [];

    foreach ($rows as $row) {
      if (!isset($data[$row->state])) {
        $data[$row->state] = [0, 0, 0];
      }

      $data[$row->state][$row->status] = $row->count;
      $totals[$row->status] += $row->count;
    }

    ksort($data);
    $data['Totals'] = $totals;

    return [
      '#theme' => 'ham_neighbors_report',
      '#data' => $data,
      '#cache' => [
        'max-age' => 1800,
      ],
    ];
  }
}
