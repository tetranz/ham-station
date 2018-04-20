<?php

namespace Drupal\ham_station\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ham_station\Neighbors\HamNeighborsService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Ham neighbors page.
   *
   * @param string $callsign
   *   Callsign
   *
   * @return array
   *   Render array
   */
  public function hamNeighbors($callsign = NULL) {
    $callsign = strtoupper(trim($callsign));

    /** @var HamNeighborsService $service */
    $service = \Drupal::service('ham_station.ham_neighbors');

    return $service->render($callsign);
  }

  /**
   * Ham neighbors ajax request.
   *
   * @param $callsign
   *   Callsign.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function hamNeighborsAjax($callsign) {
    $callsign = strtoupper(trim($callsign));

    /** @var HamNeighborsService $service */
    $service = \Drupal::service('ham_station.ham_neighbors');

    return new Response(
      $service->processSearchRequest($callsign)->responseString()
    );
  }

  public function statesDoneAjax() {
    /** @var \Drupal\ham_station\ReportService $reportService */
    $reportService = \Drupal::service('ham_station.report_service');

    $result = $reportService->geocodeStatus();
    
    return new AjaxResponse([
      'done' => $result['done'],
      'working_on' => $result['working_on'],
    ]);
  }
}
