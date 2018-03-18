<?php

namespace Drupal\ham_station\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form.
 */
class HamStationConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ham_station_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ham_station.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ham_station.settings');

    $key = 'google_api_key';
    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API key'),
      '#default_value' => $config->get($key),
    ];

    $key = 'geocode_batch_size';
    $form[$key] = [
      '#type' => 'number',
      '#title' => $this->t('Number of geocoding attempted on each cron.'),
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $config->get($key),
    ];

    $key = 'extra_batch_query_where';
    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra batch query where'),
      '#description' => $this->t('Arbitary WHERE condition to add to the batch query. Intended for debugging.'),
      '#default_value' => $config->get($key),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ham_station.settings');
    $keys = ['google_api_key', 'geocode_batch_size', 'extra_batch_query_where'];

    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
