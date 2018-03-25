<?php

namespace Drupal\ham_station\Neighbors;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Render\RendererInterface;
use Drupal\ham_station\Entity\HamStation;
use Drupal\ham_station\Form\HamNeighborsForm;

/**
 * Functionality for the ham neighbors page.
 */
class HamNeighborsService {

  const STATUS_NO_CALLSIGN = 0;
  const STATUS_OK = 1;
  const STATUS_NOT_GEOCODED = 2;
  const STATUS_GEO_NOT_FOUND = 3;
  const STATUS_NOT_IN_DB = 4;

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
   * HamNeighborsService constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(FormBuilder $form_builder, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->formBuilder = $form_builder;
    $this->hamStationStorage = $entity_type_manager->getStorage('ham_station');
    $this->blockContentStorage = $entity_type_manager->getStorage('block_content');
    $this->renderer = $renderer;
  }

  /**
   * Generate the render array for the neighbors.
   *
   * @param string $callsign
   *   The callsign to query
   *
   * @return array
   *   Render array.
   */
  public function render($callsign) {
    $form = $this->formBuilder->getForm(HamNeighborsForm::class, $callsign);

    // Use callsign from the URL for an initial query. This makes page
    // bookmarkable and means that non-Javascript users still get the list.
    $response = $this->processSearchRequest($callsign);

    $info = NULL;
    $block_entity = $this->blockContentStorage->loadByProperties([
      'info' => 'neighbors-info'
    ]);

    if (!empty($block_entity)) {
      $block_entity = reset($block_entity);
      $info = $block_entity->body->value;
    }

    return [
      '#theme' => 'ham_neighbors',
      '#form' => $form,
      '#callsign' => $callsign,
      '#message' => $response->message,
      '#view' => $response->view,
      '#info' => $info,
      '#attached' => [
        'library' => ['ham_station/neighbors'],
        'drupalSettings' => [
          'ham_neighbors' => [
            'status' => $response->status,
            'lat' => $response->lat,
            'lng' => $response->lng,
          ],
        ],
      ],
    ];
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
  public function processSearchRequest($callsign) {
    $return = new ResponseDTO();
    $return->callsign = $callsign;

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

    $return->lat = $entity->field_location->lat;
    $return->lng = $entity->field_location->lng;

    // Looks good so generate the view render array.
    $arg = sprintf('%s,%s<100miles', $return->lat, $return->lng);
    $render_array = views_embed_view('ham_neighbors', 'default', $arg);
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
