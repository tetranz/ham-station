<?php

namespace Drupal\ham_station;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;

class Geocodio {

  /**
   * @var Client
   */
  private $client;

  /**
   * @var ConfigFactory
   */
  private $configFactory;

  public function __construct(Client $client, ConfigFactory $config_factory) {
    $this->client = $client;
    $this->configFactory = $config_factory;
  }

  public function getPostalCode($postal_code) {
    $response = $this->makeRequest(['postal_code' => $postal_code]);
    if (empty($response)) {
      return NULL;
    }

    return $response['results']['postal_code']['response']['results'][0]['location'] ?? NULL;
  }

  public function makeRequest($data) {
    $response = $this->client->request('POST', 'https://api.geocod.io/v1.3/geocode', [
      'json' => $data,
      'query' => ['api_key' => $this->configFactory->get('ham_station.settings')->get('geocodio_api_key')],
      'http_errors' => FALSE,
    ]);

    if ($response->getStatusCode() !== 200) {
      return NULL;
    }

    return Json::decode($response->getBody());
  }
}

// $body["results"]["postal_code"]["response"]["results"][0]["location"]["lat"]