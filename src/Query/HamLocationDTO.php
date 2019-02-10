<?php

namespace Drupal\ham_station\Query;

class HamLocationDTO {

  private $id;
  private $lat;
  private $lng;
  private $addresses  = [];

  public function __construct($id, $lat, $lng) {
    $this->id = $id;
    $this->lat = $lat;
    $this->lng = $lng;
  }

  public function getId() {
    return $this->id;
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

  public function moveAddressToTop($top_idx) {
    $address = $this->addresses[$top_idx];

    if ($top_idx == 0) {
      return $address;
    }

    unset($this->addresses[$top_idx]);
    array_unshift($this->addresses, $address);
    return $address;
  }

}
