<?php

namespace Drupal\ham_station;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Client;

class GoogleGeocoder {

  const API_ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

  /**
   * @var Client
   */
  private $client;
  private $config;

  public function __construct(Client $client, $config) {
    $this->client = $client;
    $this->config = $config;
  }

  public function geocodePostalCode($code) {
    $response = $this->makeRequest(['components' => 'postal_code:' . $code]);
    $location = $response['results'][0]['geometry']['location'] ?? NULL;

    if (empty($location)) {
      return NULL;
    }

    return $location;
  }

  public function makeRequest($query) {
    $query = ['key' => $this->config->get('google_geocode_api_key')] + $query;
    $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json', [
      'query' => $query,
      'http_errors' => FALSE,
    ]);

    if ($response->getStatusCode() !== 200) {
      return NULL;
    }

    $response = Json::decode($response->getBody());
    if ($response['status'] !== 'OK') {
      return NULL;
    }

    return $response;
  }
}

// $a["status"]
// $a["results"][0]["geometry"]["location"]["lat"]