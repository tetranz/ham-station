<?php

namespace Drupal\ham_station\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\ham_station\Geocoder;
use Drupal\ham_station\Importers\FccImporter;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class HamStationCommands extends DrushCommands {

  /**
   * The FCC importer.
   *
   * @var FccImporter
   */
  private $fccImporter;

  /**
   * The geocoder service.
   *
   * @var Geocoder
   */
  private $geocoder;

  public function __construct(FccImporter $fcc_importer, Geocoder $geocoder) {
    $this->fccImporter = $fcc_importer;
    $this->geocoder = $geocoder;
  }

  /**
   * Import new licenses from fcc_ham_data module.
   *
   * @usage ham_station:import-fcc-new
   *   Usage Import new licenses from fcc_ham_data module.
   *
   * @command ham_station:import-fcc-new
   * @aliases hsifccn
   */
  public function importFccNew() {
    $this->fccImporter->importNewLicenses([$this->io(), 'writeln']);
  }

  /**
   * Update existing licenses from fcc_ham_data module.
   *
   * @usage ham_station:import-fcc-update
   *   Usage existing licenses from fcc_ham_data module.
   *
   * @command ham_station:import-fcc-update
   * @aliases hsifccu
   */
  public function importFccUpdate() {
    $this->fccImporter->updateLicenses([$this->io(), 'writeln']);
  }

  /**
   * Geocode some addresses.
   *
   * @usage ham_station:geocode
   *   Geocode some addresses.
   *
   * @command ham_station:geocode
   * @aliases hsigeo
   */
  public function geocode() {
    $this->geocoder->geoCode([$this->io(), 'writeln']);
  }

}
