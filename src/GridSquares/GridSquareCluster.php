<?php

namespace Drupal\ham_station\GridSquares;

class GridSquareCluster {

  private $center;
  private $northWest;
  private $north;
  private $northEast;
  private $east;
  private $southEast;
  private $south;
  private $southWest;
  private $west;

  /**
   * GridSquareCluster constructor.
   *
   * @param $center
   * @param $northWest
   * @param $north
   * @param $northEast
   * @param $east
   * @param $southEast
   * @param $south
   * @param $southWest
   * @param $west
   */
  public function __construct($center, $northWest, $north, $northEast, $east, $southEast, $south, $southWest, $west)
  {
    $this->center = $center;
    $this->northWest = $northWest;
    $this->north = $north;
    $this->northEast = $northEast;
    $this->east = $east;
    $this->southEast = $southEast;
    $this->south = $south;
    $this->southWest = $southWest;
    $this->west = $west;
  }

  /**
   * @return mixed
   */
  public function getCenter()
  {
    return $this->center;
  }

  /**
   * @return mixed
   */
  public function getNorthWest()
  {
    return $this->northWest;
  }

  /**
   * @return mixed
   */
  public function getNorth()
  {
    return $this->north;
  }

  /**
   * @return mixed
   */
  public function getNorthEast()
  {
    return $this->northEast;
  }

  /**
   * @return mixed
   */
  public function getEast()
  {
    return $this->east;
  }

  /**
   * @return mixed
   */
  public function getSouthEast()
  {
    return $this->southEast;
  }

  /**
   * @return mixed
   */
  public function getSouth()
  {
    return $this->south;
  }

  /**
   * @return mixed
   */
  public function getSouthWest()
  {
    return $this->southWest;
  }

  /**
   * @return mixed
   */
  public function getWest()
  {
    return $this->west;
  }

}
