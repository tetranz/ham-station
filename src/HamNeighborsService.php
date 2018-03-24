<?php

namespace Drupal\ham_station;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilder;
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
   * @var FormBuilder
   */
  private $formBuilder;

  /**
   * Entity storage for ham_station.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $hamStationStorage;

  /**
   * The view render array.
   *
   * @var array
   */
  private $viewRenderArray;

  /**
   * Latitude of queried station.
   *
   * @var float
   */
  private $lat;

  /**
   * Longitude of queried station.
   *
   * @var float
   */
  private $lng;

  public function __construct(FormBuilder $form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->formBuilder = $form_builder;
    $this->hamStationStorage = $entity_type_manager->getStorage('ham_station');
  }

  /**
   * Render the result of a neighbors query.
   *
   * @param string $callsign
   *   The callsign to query
   *
   * @return array
   *   Render array.
   */
  public function render($callsign) {
    $form = $this->formBuilder->getForm(HamNeighborsForm::class, $callsign);

    $status = $this->processRequest($callsign);

    return [
      '#theme' => 'ham_neighbors',
      '#form' => $form,
      '#callsign' => $callsign,
      '#status' => $status,
      '#view' => $this->viewRenderArray,
      '#attached' => [
        'library' => ['ham_station/neighbors'],
        'drupalSettings' => [
          'ham_neighbors' => [
            'lat' => $this->lat,
            'lng' => $this->lng,
          ]
        ]
      ],
    ];
  }

  /**
   * Process the request for a callsign.
   * 
   * @param string $callsign
   *   The callsign to query.
   *
   * @return int
   *   The status.
   */
  private function processRequest($callsign) {
    $this->viewRenderArray = NULL;
    $this->lat = NULL;
    $this->lng = NULL;

    if (empty($callsign)) {
      return self::STATUS_NO_CALLSIGN;
    }

    $entity = $this->callsignQuery($callsign);

    if (empty($entity)) {
      return self::STATUS_NOT_IN_DB;
    }

    if ($entity->geocode_status->value == HamStation::GEOCODE_STATUS_PENDING) {
      return self::STATUS_NOT_GEOCODED;
    }

    if ($entity->geocode_status->value == HamStation::GEOCODE_STATUS_NOT_FOUND) {
      return self::STATUS_GEO_NOT_FOUND;
    }

    $this->lat = $entity->field_location->lat;
    $this->lng = $entity->field_location->lng;

    // Looks good so generate the view render array.
    $arg = sprintf('%s,%s<100miles', $this->lat, $this->lng);
    $this->viewRenderArray = views_embed_view('ham_neighbors', 'default', $arg);

    return self::STATUS_OK;
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
