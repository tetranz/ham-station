<?php

namespace Drupal\ham_station\Importers;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\ham_station\Entity\HamStation;
use Psr\Log\LoggerInterface;

class FccImporter {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $db_connection;

  /**
   * The logger.
   *
   * @var LoggerInterface
   */
  private $logger;

  /**
   * FccImporter constructor.
   *
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connection.
   */
  public function __construct(Connection $db_connection, LoggerInterface $logger) {
    $this->db_connection = $db_connection;
    $this->logger = $logger;
  }

  /**
   * Import new FCC licenses.
   */
  public function importNewLicenses() {

    // Drupal best practices would be to use the entity API. Unfortunately that
    // is quite slow when importing a large number of entities. Direct
    // (MySQL specific) SQL used here is about 150 times faster. Our entity has
    // no revisions or translations and we're only importing into base fields
    // so it is a simple write to a single table. On a laptop this takes less
    // than a minute to create 800K entities.

    $sql = '
INSERT INTO ham_station
(uuid, langcode, callsign, first_name, middle_name, last_name, suffix,
address__address_line1, address__locality, address__administrative_area, address__postal_code, address__country_code,
organization, operator_class, previous_callsign, address_type, geolocation_status, total_hash, address_hash,
user_id, status, created, changed
)
SELECT uuid(), \'en\', hd.call_sign AS callsign, en.first_name, en.mi AS middle_name, en.last_name, en.suffix,
en.street_address AS address__address_line1, en.city AS address__locality, en.state AS address__administrative_area,
en.zip_code AS address__postal_code, \'US\' AS address__country_code,
CASE WHEN applicant_type_code != \'I\' THEN en.entity_name ELSE NULL END AS organization, 
am.operator_class, am.previous_callsign, 0 AS address_type, 0 AS geolocation_status, hd.total_hash, en.address_hash,
1 AS user_id, 1 AS status, unix_timestamp() AS created, unix_timestamp() AS changed
FROM fcc_license_hd hd
INNER JOIN fcc_license_en en ON en.unique_system_identifier = hd.unique_system_identifier
INNER JOIN fcc_license_am am ON am.unique_system_identifier = hd.unique_system_identifier
LEFT JOIN ham_station hs ON hs.callsign = hd.call_sign
WHERE hd.license_status = \'A\' AND hs.callsign IS NULL';

    $row_count = $this->db_connection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $this->logger->info(sprintf('%s new FCC licenses imported.', $row_count));
  }

  /**
   * Update existing FCC licenses.
   */
  public function updateLicenses() {
    // Update existing callsigns. Geolocation status is only changed if the
    // address has changed.
    $sql = '
UPDATE ham_station hs
INNER JOIN fcc_license_hd hd ON hd.call_sign = hs.callsign
INNER JOIN fcc_license_en en ON en.unique_system_identifier = hd.unique_system_identifier
INNER JOIN fcc_license_am am ON am.unique_system_identifier = hd.unique_system_identifier
SET 
hs.first_name = en.first_name, 
hs.middle_name = en.mi,
hs.last_name = en.last_name,
hs.suffix = en.suffix,
hs.address__address_line1 = en.street_address,
hs.address__locality = en.city,
hs.address__administrative_area = en.state,
hs.address__postal_code = en.zip_code,
hs.organization = CASE WHEN applicant_type_code != \'I\' THEN en.entity_name ELSE NULL END,
hs.operator_class = am.operator_class,
hs.previous_callsign = am.previous_callsign,
hs.address_type = hs.address_type,
hs.geolocation_status = CASE WHEN hs.address_hash != en.address_hash THEN 0 ELSE hs.geolocation_status END,
hs.total_hash = hd.total_hash,
hs.address_hash = en.address_hash,
hs.changed = unix_timestamp()
WHERE hd.license_status = \'A\' AND hd.total_hash != hs.total_hash';

    $row_count = $this->db_connection->query($sql, [], ['return' => Database::RETURN_AFFECTED]);

    $this->logger->info(sprintf('%s existing FCC licenses updated.', $row_count));
  }

}
