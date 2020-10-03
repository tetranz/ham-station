<?php

namespace Drupal\ham_station\Importers;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\ham_station\Query\MapQueryService;
use Psr\Log\LoggerInterface;

class FccImporter {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $dbConnection;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * FccImporter constructor.
   *
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(Connection $db_connection, LoggerInterface $logger) {
    $this->dbConnection = $db_connection;
    $this->logger = $logger;
  }

  /**
   * Import new FCC licenses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function importNewLicenses(callable $callback) {

    // Drupal best practices would be to use the entity API. Unfortunately that
    // is quite slow when importing a large number of entities. Direct
    // (MySQL specific) SQL used here is about 150 times faster. Our entity has
    // no revisions or translations and we're only importing into base fields
    // so it is a simple write to a single table. On a laptop this takes less
    // than a minute to create 800K entities.

    $sql = '
    INSERT INTO {ham_station}
    (uuid, langcode, callsign, first_name, middle_name, last_name, suffix,
    organization, operator_class, previous_callsign, total_hash, address_hash,
    user_id, status, created, changed)
    SELECT uuid() AS uuid, \'en\' AS langcode, hd.call_sign AS callsign, en.first_name, en.mi AS middle_name, en.last_name, en.suffix,
    CASE WHEN applicant_type_code != \'I\' THEN en.entity_name ELSE NULL END AS organization, 
    am.operator_class, am.previous_callsign, hd.total_hash, en.address_hash,
    1 AS user_id, 1 AS status, unix_timestamp() AS created, unix_timestamp() AS changed    
    FROM {fcc_license_hd} hd
    INNER JOIN {fcc_license_en} en ON en.unique_system_identifier = hd.unique_system_identifier
    INNER JOIN {fcc_license_am} am ON am.unique_system_identifier = hd.unique_system_identifier
    WHERE hd.license_status = \'A\'
    AND NOT EXISTS (SELECT id FROM {ham_station} hs WHERE hs.callsign = hd.call_sign)';

    $row_count = $this->dbConnection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $msg = sprintf('%s new FCC licenses imported.', $row_count);
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

  /**
   * Update existing FCC licenses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function updateLicenses(callable $callback = NULL) {
    // Update existing callsigns.
    $sql = '
    UPDATE {ham_station} hs
    INNER JOIN {fcc_license_hd} hd ON hd.call_sign = hs.callsign
    INNER JOIN {fcc_license_en} en ON en.unique_system_identifier = hd.unique_system_identifier
    INNER JOIN {fcc_license_am} am ON am.unique_system_identifier = hd.unique_system_identifier
    SET 
    hs.first_name = en.first_name, 
    hs.middle_name = en.mi,
    hs.last_name = en.last_name,
    hs.suffix = en.suffix,
    hs.organization = CASE WHEN applicant_type_code != \'I\' THEN en.entity_name ELSE NULL END,
    hs.operator_class = am.operator_class,
    hs.previous_callsign = am.previous_callsign,
    hs.total_hash = hd.total_hash,
    hs.address_hash = en.address_hash,
    hs.changed = unix_timestamp()
    WHERE hd.license_status = \'A\'';

    $row_count = $this->dbConnection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $msg = sprintf('%s existing FCC licenses updated.', $row_count);
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

  /**
   * Import new FCC addresses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function importNewAddresses(callable $callback) {
    $sql = '
    INSERT INTO {ham_address}
    (uuid, langcode, hash,
    address__address_line1, address__locality, address__administrative_area,
    address__postal_code, address__country_code, geocode_status,
    user_id, status, created, changed)
    SELECT UUID() AS uuid, \'en\' AS langcode, address_hash as hash,
    en.street_address AS address__address_line1, en.city AS address__locality, en.state AS address__administrative_area,
    en.zip_code AS address__postal_code, \'US\' AS address__country_code, 0 AS geocode_status,
    1 AS user_id, 1 AS status, unix_timestamp() AS created, unix_timestamp() AS changed
    FROM {fcc_license_en} en
    INNER JOIN {fcc_license_hd} hd ON hd.unique_system_identifier = en.unique_system_identifier AND hd.license_status = \'A\'
    WHERE NOT EXISTS (SELECT id FROM {ham_address} ha WHERE ha.hash = en.address_hash)
    AND en.unique_system_identifier = (
    SELECT MIN(en2.unique_system_identifier) 
    FROM {fcc_license_en} en2 INNER JOIN {fcc_license_hd} hd2 ON hd2.unique_system_identifier = en2.unique_system_identifier
    WHERE hd2.license_status = \'A\' AND en2.address_hash = en.address_hash)';

    $row_count = $this->dbConnection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $msg = sprintf('%s new FCC addresses imported.', $row_count);
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

  /**
   * Delete inactive FCC licenses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function deleteInactiveStations(callable $callback) {
    $sql = '
    DELETE ham_station
    FROM ham_station
    LEFT JOIN fcc_license_hd hd ON hd.call_sign = ham_station.callsign AND hd.license_status = \'A\'
    WHERE hd.unique_system_identifier IS NULL';

    $row_count = $this->dbConnection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $msg = sprintf('%s inactive FCC stations deleted.', $row_count);
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

  /**
   * Delete inactive FCC addresses.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function deleteInactiveAddresses(callable $callback) {
    $sql = '
    DELETE ham_address
    FROM ham_address
    LEFT JOIN ham_station hs ON hs.address_hash = ham_address.hash
    WHERE hs.id IS NULL';

    $row_count = $this->dbConnection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $msg = sprintf('%s inactive FCC addresses deleted.', $row_count);
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

  /**
   * Delete inactive FCC locations.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function deleteInactiveLocations(callable $callback) {
    $sql = '
    DELETE ham_location
    FROM ham_location
    LEFT JOIN ham_address ha ON ha.location_id = ham_location.id
    WHERE ha.id IS NULL';

    $row_count = $this->dbConnection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $msg = sprintf('%s inactive FCC locations deleted.', $row_count);
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

  /**
   * Set some addresses as likely being a PO Box.
   *
   * @param callable $callback
   */
  public function setPoBox(callable $callback) {
    // Don't override manual settings.
    $sql = '
    UPDATE ham_address
    SET geocode_status = :pobox_status
    WHERE address__address_line1 LIKE :pobox_like
    AND (geocode_provider IS NULL OR geocode_provider != \'mn\')';

    $row_count = $this->dbConnection->query(
      $sql,
      [':pobox_status' => MapQueryService::GEOCODE_STATUS_PO_BOX, ':pobox_like' => 'PO Box%'],
      ['return' => Database::RETURN_AFFECTED]
    );

    $msg = sprintf('%s addresses set as PO Box.', $row_count);
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

}
