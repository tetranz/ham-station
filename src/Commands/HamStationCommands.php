<?php

namespace Drupal\ham_station\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
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
   * Import new licenses from fcc_ham_data module.
   *
   * @usage ham_station:import-fcc-new
   *   Usage Import data from fcc_ham_data module.
   *
   * @command ham_station:import-fcc-new
   * @aliases hsifccn
   */
  public function importFccNew() {
    $this->fccImporter->importNew();
  }

}
