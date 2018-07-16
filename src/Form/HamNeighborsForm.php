<?php

namespace Drupal\ham_station\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for the ham neighbors page.
 */
class HamNeighborsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ham_neighbors_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  //  $args = func_get_args();
  //  $query = $args[2];

  //  $form['#attributes'] = [
  //    'class' => [ 'neighbors-form'],
  //  ];

    $form['query_type'] = [
      '#type' => 'radios',
      '#options' => [
        'c' => $this->t('Callsign'),
        'g' => $this->t('Gridsquare'),
        'a' => $this->t('Street address'),
        'm' => $this->t('Latitude and longitude'),
      ],
    ];

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callsign'),
      '#description' => $this->t('Enter a callsign'),
   //   '#default_value' => $query,
    ];

    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street address'),
      '#description' => $this->t('Start typing a street address and choose from the dropdown.'),
      '#wrapper_attributes' => [
        'style' => 'display:none',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary']
      ],
      '#suffix' => '<span class="ajax-processing hidden"><strong>Processing...</strong></span>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'ham_station.ham_neighbors',
      ['callsign' => $form_state->getValue('callsign')]
    );
  }

}
