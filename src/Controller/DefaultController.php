<?php

namespace Drupal\ham_station\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\ham_station\Form\HamMapForm;
use Drupal\ham_station\GridSquares\GridSquareService;
use Drupal\ham_station\ReportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * @var GridSquareService
   */
  private $gridSquareService;

  /**
   * @var ReportService
   */
  private $reportService;

  /**
   * @var Serializer
   */
  private $serializer;

  /**
   * @var Renderer
   */
  private $renderer;

  public function __construct(
    GridSquareService $grid_square_service,
    ReportService $report_service,
    Serializer $serializer,
    Renderer $renderer
  ) {
    $this->gridSquareService = $grid_square_service;
    $this->reportService = $report_service;
    $this->serializer = $serializer;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ham_station.gridsquare_service'),
      $container->get('ham_station.report_service'),
      $container->get('serializer'),
      $container->get('renderer')
    );
  }

  /**
   * Ham map page.
   *
   * @param string $query_type|null
   *   Initial query type.
   * @param string $query_value|null
   *   Initial query value.
   *
   * @return array
   */
  public function hamMap($query_type, $query_value) {

    $block_content_storage = $this->entityTypeManager()->getStorage('block_content');

    $block_ids = $block_content_storage->getQuery()
      ->condition('info', 'neighbors-info-', 'STARTS_WITH')
      ->execute();

    $blocks = $block_content_storage->loadMultiple($block_ids);
    $info_blocks = [];

    foreach($blocks as $block) {
      $info_blocks[substr($block->info->value, strlen('neighbors-info-'))] = $block->body->value;
    }

    return [
      '#theme' => 'ham_neighbors',
      '#form' => $this->formBuilder()->getForm(HamMapForm::class),
      '#info_blocks' => $info_blocks,
      '#attached' => [
        'library' => ['ham_station/neighbors'],
        'drupalSettings' => [
          'ham_station' => ['query_type' => $query_type, 'query_value' => $query_value],
        ]
      ],
    ];
  }

  public function hamMapAjax(Request $request) {
    $query_type = $request->get('queryType');
    $query_value = $request->get('value');

    $result = $this->gridSquareService->mapQuery($query_type, $query_value);
    if (empty($result)) {
      return new JsonResponse([
        'error' => $this->gridSquareService->getErrorMessage(),
      ]);
    }

    $data = $this->serializer->serialize($result, 'json');

    $response = new JsonResponse();
    $response->setJson($data);

    return $response;
  }

  /**
   * Get the geocode status report.
   *
   * Use ajax for this so can use the anonymous page cache.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function geocodeReportAjax() {
    $result = $this->reportService->geocodeStatus();

    $render_array = [
      '#theme' => 'ham_neighbors_report',
      '#state_counts' => $result['states'],
      '#totals'  => $result['totals'],
      '#success_pc' => $result['success_pc'],
    ];

    return new Response(
      $this->renderer->render($render_array)
    );
  }

}
