<?php

namespace Drupal\ham_station\Neighbors;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Render\RendererInterface;
use Drupal\ham_station\Entity\HamStation;
use Drupal\ham_station\Form\HamMapForm;
use Drupal\ham_station\Form\HamNeighborsForm;
use Drupal\ham_station\GridSquares\Calculator;
use Drupal\ham_station\GridSquares\SubSquare;
use Drupal\ham_station\ReportService;

/**
 * Functionality for the ham neighbors page.
 */
class HamNeighborsService {

  const STATUS_NO_CALLSIGN = 0;
  const STATUS_OK = 1;
  const STATUS_NOT_GEOCODED = 2;
  const STATUS_GEO_NOT_FOUND = 3;
  const STATUS_NOT_IN_DB = 4;

  const QUERY_TYPE_CALLSIGN = 0;
  const QUERY_TYPE_GRIDSQUARE = 1;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  private $formBuilder;

  /**
   * Entity storage for block_content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $blockContentStorage;

  /**
   * HamNeighborsService constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(FormBuilder $form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->formBuilder = $form_builder;
    $this->blockContentStorage = $entity_type_manager->getStorage('block_content');
  }

  /**
   * Generate the render array for the map page.
   *
   * @param string $query_type|null
   *   Initial query type.
   * @param string $query_value|null
   *   Initial query value.
   *
   * @return array
   */
  public function render($query_type, $query_value) {
    $form = $this->formBuilder->getForm(HamMapForm::class);

    $block_ids = $this->blockContentStorage->getQuery()
      ->condition('info', 'neighbors-info-', 'STARTS_WITH')
      ->execute();

    $blocks = $this->blockContentStorage->loadMultiple($block_ids);
    $info_blocks = [];

    foreach($blocks as $block) {
      $info_blocks[substr($block->info->value, strlen('neighbors-info-'))] = $block->body->value;
    }

    return [
      '#theme' => 'ham_neighbors',
      '#form' => $form,
      '#info_blocks' => $info_blocks,
      '#attached' => [
        'library' => ['ham_station/neighbors'],
        'drupalSettings' => [
          'ham_station' => ['query_type' => $query_type, 'query_value' => $query_value],
        ]
      ],
    ];
  }
}
