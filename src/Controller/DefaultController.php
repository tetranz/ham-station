<?php

namespace Drupal\ham_station\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ham_station\HamNeighborsService;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * The ham neighbors page.
   *
   * @param string $callsign
   *   Callsign
   *
   * @return array
   *   Render array
   */
  public function hamNeighbors($callsign = NULL) {
    /** @var HamNeighborsService $service */
    $service = \Drupal::service('ham_station.ham_neighbors');

    return $service->render($callsign);
  }

}
