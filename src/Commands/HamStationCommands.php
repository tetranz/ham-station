<?php

namespace Drupal\ham_station\Commands;

use Drupal\ham_station\Geocoder;
use Drupal\ham_station\Importers\FccImporter;
use Drupal\ham_station\OSMGeocoder;
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

  /**
   * OSM geocoding service.
   *
   * @var OSMGeocoder
   */
  private $osmGeocoder;

  public function __construct(FccImporter $fcc_importer, Geocoder $geocoder, OSMGeocoder $osm_geocoder) {
    $this->fccImporter = $fcc_importer;
    $this->geocoder = $geocoder;
    $this->osmGeocoder = $osm_geocoder;
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

  /**
   * Copy geocode results for duplicate addresses
   *
   * @usage ham_station:copygeo
   *   Copy geocode results for duplicate addresses.
   *
   * @command ham_station:copygeo
   * @aliases hsicpgeo
   */
  public function copyGeocodeForDuplicates() {
    $this->geocoder->copyGeocodeForDuplicates([$this->io(), 'writeln']);
  }

  /**
   * reload lat and lng from stored json.
   *
   * @usage ham_station:reloadlatlng
   *   reload lat and lng from stored json.
   *
   * @command ham_station:reloadlatlng
   */
  public function reloadLatlng() {
    $this->geocoder->reloadLatLng([$this->io(), 'writeln']);
  }

  /**
   * Geocode from OSM data.
   *
   * @param $id_suffix
   *   Last two digits of entity id.
   *
   * @usage ham_station:osmgeocode
   *   Geocode some addresses.
   *
   * @command ham_station:osmgeocode
   * @aliases hsiosmgeo
   */
  public function osmgeocode($id_suffix) {
    $this->osmGeocoder->geoCode($id_suffix, [$this->io(), 'writeln']);
  }

}
