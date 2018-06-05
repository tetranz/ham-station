<?php

namespace Drupal\ham_station\Neighbors;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Render\RendererInterface;
use Drupal\ham_station\Entity\HamStation;
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
   * Entity storage for ham_station.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $hamStationStorage;

  /**
   * Entity storage for block_content.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $blockContentStorage;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * The report service.
   *
   * @var \Drupal\ham_station\ReportService
   */
  private $reportService;

  /**
   * Grid squares calculator.
   *
   * @var \Drupal\ham_station\GridSquares\Calculator
   */
  private $calculator;

  /**
   * HamNeighborsService constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\ham_station\ReportService $report_service
   *   The report service.
   * @param \Drupal\ham_station\GridSquares\Calculator $calculator
   *   Grid squares calculator.
   */
  public function __construct(FormBuilder $form_builder, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, ReportService $reportService, Calculator $calculator) {
    $this->formBuilder = $form_builder;
    $this->hamStationStorage = $entity_type_manager->getStorage('ham_station');
    $this->blockContentStorage = $entity_type_manager->getStorage('block_content');
    $this->renderer = $renderer;
    $this->reportService = $reportService;
    $this->calculator = $calculator;
  }

  /**
   * Generate the render array for the neighbors.
   *
   * @param string $query
   *   The callsign or grid square to query.
   *
   * @return array
   *   Render array.
   */
  public function render($query) {
    $form = $this->formBuilder->getForm(HamNeighborsForm::class, $query);

    // Use callsign from the URL for an initial query. This makes page
    // bookmarkable and means that non-Javascript users still get the list.
    $response = $this->processSearchRequest($query);

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
      '#query_type' => $response->queryType,
      '#query' => $query,
      '#message' => $response->message,
      '#view' => $response->view,
      '#info_blocks' => $info_blocks,
      '#attached' => [
        'library' => ['ham_station/neighbors'],
        'drupalSettings' => [
          'ham_neighbors' => [
            'query_type' => $response->queryType,
            'status' => $response->status,
            'lat' => $response->lat,
            'lng' => $response->lng,
            'north' => $response->gridNorth,
            'south' => $response->gridSouth,
            'east' => $response->gridEast,
            'west' => $response->gridWest,
          ],
        ],
      ],
    ];
  }

  public function processSearchRequest($query) {
    if (preg_match('/[A-R]{2}\d{2}[A-X]{2}/', $query) === 1) {
      return $this->processGridSquareRequest($query);
    }

    return $this->processCallsignSearch($query);
  }

  /**
   * Process the search for a callsign's neighbors.
   *
   * @param string $callsign
   *  The callsign.
   *
   * @return ResponseDTO
   *   The response.
   */
  public function processCallsignSearch($callsign) {
    $return = new ResponseDTO();
    $return->queryType = self::QUERY_TYPE_CALLSIGN;
    $return->query = $callsign;

    if (empty($callsign)) {
      $return->status = self::STATUS_NO_CALLSIGN;
      return $return;
    }

    $entity = $this->callsignQuery($callsign);

    if (empty($entity)) {
      $return->status = self::STATUS_NOT_IN_DB;
      $return->message = sprintf('Sorry, %s is not in the database.', $callsign);
      return $return;
    }

    if ($entity->geocode_status->value == HamStation::GEOCODE_STATUS_PENDING) {
      $return->status = self::STATUS_NOT_GEOCODED;
      $return->message = sprintf('Sorry, we have not geocoded %s yet.', $callsign);
      return $return;
    }

    if ($entity->geocode_status->value == HamStation::GEOCODE_STATUS_NOT_FOUND) {
      $return->message = sprintf('Sorry, the geocoding service was not able to find %s\'s address.', $callsign);
      $return->status = self::STATUS_GEO_NOT_FOUND;
      return $return;
    }

    $return->lat = $entity->latitude->value;
    $return->lng = $entity->longitude->value;

    $return->gridSubSquare = $this->calculator->latLngToSubSquare($return->lat, $return->lng);
    $gridSquare = new SubSquare($return->gridSubSquare);
    $return->gridNorth = $gridSquare->getLatNorth();
    $return->gridSouth = $gridSquare->getLatSouth();
    $return->gridEast = $gridSquare->getLngEast();
    $return->gridWest = $gridSquare->getLngWest();

    // Looks good so generate the view render array.
    $arg = sprintf('%s|%s|25|miles', $return->lat, $return->lng);
    $render_array = views_embed_view('ham_neighbors', 'dist_from_point', $arg);
    $return->view = $this->renderer->render($render_array);
    $return->status = self::STATUS_OK;

    return $return;
  }

  public function processGridSquareRequest($query) {

    $return = new ResponseDTO();
    $return->queryType = self::QUERY_TYPE_GRIDSQUARE;
    $return->query = $query;
    $gridSquare = new SubSquare($query);

    $return->lat = $gridSquare->getLatCenter();
    $return->lng = $gridSquare->getLngCenter();
    $return->gridNorth = $gridSquare->getLatNorth();
    $return->gridSouth = $gridSquare->getLatSouth();
    $return->gridEast = $gridSquare->getLngEast();
    $return->gridWest = $gridSquare->getLngWest();

    $arg = sprintf('%s|%s|%s|%s|25|miles',
      $gridSquare->getLatSouth(),
      $gridSquare->getLngWest(),
      $gridSquare->getLatNorth(),
      $gridSquare->getLngEast()
    );

    $render_array = views_embed_view('ham_neighbors', 'rectangle', $arg);
    $return->view = $this->renderer->render($render_array);
    $return->status = self::STATUS_OK;

    return $return;
  }

  /**
   * Query for a ham_station entity.
   * 
   * @param string $callsign
   *   The callsign.
   *
   * @return \Drupal\ham_station\Entity\HamStation|null
   *   The entity ot null.
   */
  private function callsignQuery($callsign) {
    if (empty($callsign)) {
      return NULL;
    }

    $result = $this->hamStationStorage->getQuery()
      ->condition('callsign', $callsign)
      ->execute();

    if (empty($result)) {
      return NULL;
    }

    return HamStation::load(array_keys($result)[0]);
  }

}
