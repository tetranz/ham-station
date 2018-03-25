<?php

namespace Drupal\ham_station\Neighbors;

/**
 * A simple data transfer object for the query response.
 */
class ResponseDTO {

  public $status = '';
  public $callsign = '';
  public $message = '';
  public $lat = '';
  public $lng = '';
  public $view = '';

  /**
   * Format and return the response as a string.
   * 
   * @return string
   *   The response.
   */
  public function responseString() {
    // Generate pipe delimited meta data.
    $info_line = sprintf('%s|%s|%s|%s|%s',
      $this->status,
      $this->callsign,
      $this->message,
      $this->lat,
      $this->lng
    );
    
    // Use $ as a delimiter between meta data and the render view.
    $info_line = str_replace('$', '', $info_line);
    return $info_line . '$' . $this->view;
  }

}
