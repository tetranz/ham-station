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
    $args = func_get_args();
    $callsign = strtoupper($args[2]);

    $form['#attributes'] = [
      'class' => ['form-inline', 'form-search'],
    ];

    $form['callsign'] = [
      '#type' => 'textfield',
      '#title' => 'Callsign',
      '#default_value' => $callsign,
      '#attributes' => [
        'class' => ['form-group']
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Find the neighbors'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary']
      ],
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
