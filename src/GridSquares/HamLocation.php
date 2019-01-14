<?php

namespace Drupal\ham_station\GridSquares;

class HamLocation {

  private $lat;
  private $lng;
  private $addresses  = [];

  public function __construct($lat, $lng) {
    $this->lat = $lat;
    $this->lng = $lng;
  }

  /**
   * @return mixed
   */
  public function getLat()
  {
    return $this->lat;
  }

  /**
   * @return mixed
   */
  public function getLng()
  {
    return $this->lng;
  }
  
  public function addAddress(HamAddressDTO $address) {
    $this->addresses[] = $address;
  }

  public function getAddresses() {
    return $this->addresses;
  }

}
