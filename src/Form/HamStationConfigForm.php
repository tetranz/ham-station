<?php

namespace Drupal\ham_station\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class HamStationConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'ham_station_config_form';
  }

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

    $key = 'google_maps_key'; 
    $form[$key] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $config->get($key),
    );

    $key = 'google_geocode_key';
    $form[$key] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google Geocoding API key'),
      '#default_value' => $config->get($key),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ham_station.settings');

    $config->set('google_maps_key', $form_state->getValue('google_maps_key'));
    $config->set('google_geocode_key', $form_state->getValue('google_geocode_key'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
