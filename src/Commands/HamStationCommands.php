<?php

namespace Drupal\ham_station\Commands;

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

  public function __construct(FccImporter $fcc_importer) {
    $this->fccImporter = $fcc_importer;
  }

  /**
   * Import new addresses from fcc_ham_data module.
   *
   * @usage ham_station:import-fcc-new-addresses
   *   Usage Import new addresses from fcc_ham_data module.
   *
   * @command ham_station:import-fcc-new-addresses
   * @aliases hsifccna
   */
  public function importFccNewAddresses() {
    $this->fccImporter->importNewAddresses([$this->io(), 'writeln']);
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
   *   Usage Update existing licenses from fcc_ham_data module.
   *
   * @command ham_station:import-fcc-update
   * @aliases hsifccu
   */
  public function importFccUpdate() {
    $this->fccImporter->updateLicenses([$this->io(), 'writeln']);
  }

  /**
   * Delete inactive stations from fcc_ham_data module.
   *
   * @usage ham_station:delete-fcc-inactive
   *   Usage Delete inactive licenses from fcc_ham_data module.
   *
   * @command ham_station:delete-fcc-inactive
   * @aliases hsifccd
   */
  public function importFccDeleteInactive() {
    $this->fccImporter->deleteInactiveStations([$this->io(), 'writeln']);
  }

  /**
   * Delete inactive addresses from fcc_ham_data module.
   *
   * @usage ham_station:delete-fcc-inactive-addresses
   *   Usage Delete inactive addresses from fcc_ham_data module.
   *
   * @command ham_station:delete-fcc-inactive-addresses
   * @aliases hsifccda
   */
  public function importFccDeleteInactiveAddresses() {
    $this->fccImporter->deleteInactiveAddresses([$this->io(), 'writeln']);
  }

  /**
   * Delete inactive locations from fcc_ham_data module.
   *
   * @usage ham_station:delete-fcc-inactive-locations
   *   Usage Delete inactive locations from fcc_ham_data module.
   *
   * @command ham_station:delete-fcc-inactive-locations
   * @aliases hsifccdl
   */
  public function importFccDeleteInactiveLocations() {
    $this->fccImporter->deleteInactiveLocations([$this->io(), 'writeln']);
  }

  /**
   * Set addresses as PO Box.
   *
   * @usage ham_station:set-po-box
   *   Usage Set addresses as PO Box.
   *
   * @command ham_station:set-po-box
   */
  public function setPoBoxAddresses() {
    $this->fccImporter->setPoBox([$this->io(), 'writeln']);
  }

}
