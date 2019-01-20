<?php

namespace Drupal\ham_station\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for the ham map page.
 */
class HamMapForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ham_map_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $args = func_get_args();
    $query = $args[2];

    $form['row1'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],
    ];

    $row1 = &$form['row1'];

    $row1['col_left'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['col-md-3']],
    ];

    $col_left = &$row1['col_left'];

    $col_left['query_type'] = [
      '#type' => 'radios',
      '#options' => [
        'c' => 'Callsign',
        'g' => 'Grid square',
        'z' => 'Zip code',
      ],
      '#default_value' => 'c',
    ];

    $row1['col_right'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['col-md-5']],
    ];

    $col_right = &$row1['col_right'];

    $col_right['query'] = [
      '#type' => 'textfield',
      '#title' => t('Callsign'),
      '#description' => t('Enter a callsign.'),
      '#wrapper_attributes' => ['class' => ['query-input']],
      '#default_value' => 'NF1O',
    ];

    $col_right['error'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['error-message', 'hidden']],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find the neighbors'),
      '#suffix' => '<span class="ajax-processing hidden"><strong>Processing...</strong></span>',
    ];

    $form['show_gridlabels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show grid labels'),
      '#default_value' => TRUE,
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
