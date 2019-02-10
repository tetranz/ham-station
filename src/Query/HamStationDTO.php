<?php

namespace Drupal\ham_station\Query;

class HamStationDTO {

  private $callsign;
  private $firstName;
  private $middleName;
  private $lastName;
  private $suffix;
  private $organization;
  private $operatorClass;

  public function __construct($callsign, $first_name, $middle_name, $last_name, $suffix, $organization, $operator_class) {
    $this->callsign = $callsign;
    $this->firstName = $first_name;
    $this->middleName = $middle_name;
    $this->lastName = $last_name;
    $this->suffix = $suffix;
    $this->organization = $organization;
    $this->operatorClass = $operator_class;
  }

  /**
   * @return mixed
   */
  public function getCallsign()
  {
    return $this->callsign;
  }

  public function getName() {
    if (!empty($this->organization)) {
      return $this->organization;
    }

    $parts = [$this->firstName];

    if (!empty($this->middleName)) {
      $parts[] = $this->middleName;
    }

    $parts[] = $this->lastName;

    if (!empty($this->suffix)) {
      $parts[] = $this->suffix;
    }
    
    return implode(' ', $parts);
  }

  public function getOperatorClass() {
    return $this->operatorClass;
  }

}
