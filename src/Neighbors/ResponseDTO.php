<?php

namespace Drupal\ham_station\Neighbors;

/**
 * A simple data transfer object for the query response.
 */
class ResponseDTO {

  public $queryType = '';
  public $status = '';
  public $query = '';
  public $message = '';
  public $lat = '';
  public $lng = '';
  public $gridSubSquare = '';
  public $gridNorth = '';
  public $gridSouth = '';
  public $gridEast = '';
  public $gridWest = '';
  public $view = '';

  /**
   * Format and return the response as a string.
   * 
   * @return string
   *   The response.
   */
  public function responseString() {
    // Generate pipe delimited meta data.
    $info_line = sprintf('%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s',
      $this->queryType,
      $this->status,
      $this->query,
      $this->message,
      $this->lat,
      $this->lng,
      $this->gridSubSquare,
      $this->gridNorth,
      $this->gridSouth,
      $this->gridEast,
      $this->gridWest
    );

    // Use $ as a delimiter between meta data and the render view.
    $info_line = str_replace('$', '', $info_line);
    return $info_line . '$' . $this->view;
  }

}
