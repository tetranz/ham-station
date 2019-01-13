<?php

namespace Drupal\ham_station\GridSquares;

class HamStationDTO {

  private $callsign;
  private $firstName;
  private $middleName;
  private $lastName;

  public function __construct($callsign, $first_name, $middle_name, $last_name) {
    $this->callsign = $callsign;
    $this->firstName = $first_name;
    $this->middleName = $middle_name;
    $this->lastName = $last_name;
  }

  /**
   * @return mixed
   */
  public function getCallsign()
  {
    return $this->callsign;
  }

  /**
   * @return mixed
   */
  public function getFirstName()
  {
    return $this->firstName;
  }

  /**
   * @return mixed
   */
  public function getMiddleName()
  {
    return $this->middleName;
  }

  /**
   * @return mixed
   */
  public function getLastName()
  {
    return $this->lastName;
  }

}
