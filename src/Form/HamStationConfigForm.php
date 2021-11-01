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

    $key = 'google_maps_api_key';
    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $config->get($key),
    ];

    $key = 'geocodio_api_key';
    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Geocodio API key'),
      '#default_value' => $config->get($key),
    ];

    $key = 'google_geocode_api_key';
    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google geocode API key'),
      '#default_value' => $config->get($key),
    ];

    $key = 'batch_geocoding_enable';
    $form[$key] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable batch geocoding'),
      '#description' => $this->t('Geocode a batch of addresses at regular intervals.'),
      '#default_value' => $config->get($key),
    ];

    $key = 'max_geocode_batch_size';
    $form[$key] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum geocode batch size'),
      '#description' => $this->t('Maximum number of geocodes attempted in each batch.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $config->get($key),
    ];

    $key = 'geocode_reprocess_batch_size';
    $form[$key] = [
      '#type' => 'number',
      '#title' => $this->t('Geocode reprocess batch size'),
      '#description' => $this->t('Number of previously not found addresses which are retried in each batch.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $config->get($key),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ham_station.settings');
    $keys = [
      'google_maps_api_key',
      'geocodio_api_key',
      'google_geocode_api_key',
      'batch_geocoding_enable',
      'max_geocode_batch_size',
      'geocode_reprocess_batch_size',
    ];

    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
