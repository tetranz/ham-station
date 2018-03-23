<?php

namespace Drupal\ham_station;

use Drupal\Core\Form\FormBuilder;
use Drupal\ham_station\Form\HamNeighborsForm;

/**
 * Functionality for the ham neighbors page.
 */
class HamNeighborsService {

  /**
   * The form builder.
   *
   * @var FormBuilder
   */
  private $formBuilder;

  public function __construct(FormBuilder $form_builder) {
    $this->formBuilder = $form_builder;
  }
  
  public function render($callsign) {
    $form = $this->formBuilder->getForm(HamNeighborsForm::class, $callsign);

    return [
      '#theme' => 'ham_neighbors',
      '#form' => $form,
      '#view' => views_embed_view('ham_neighbors', 'default'),
    ];
  }
  
}
