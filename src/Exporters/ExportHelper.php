<?php

namespace Drupal\ham_station\Exporters;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class to provide export to CSV functionality.
 */
class ExportHelper {

  const EXPORT_TABLE = 'ham_station_export';
  const BATCH_SIZE = 1000;

  use StringTranslationTrait;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $dbConnection;

  /**
   * Uuid generator.
   *
   * @var \Drupal\Component\Uuid\Uuid
   */
  private $uuidGenerator;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  private $messenger;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * Export helper constructor.
   *
   * @param \Drupal\Core\Database\Connection $db_connection
   *   Database connection.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   Uuid generator.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messenger.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   File system.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   */
  public function __construct(
    Connection $db_connection,
    UuidInterface $uuid_generator,
    Messenger $messenger,
    EntityTypeManagerInterface  $entity_type_manager,
    FileSystem $file_system,
    AccountProxyInterface $current_user
  ) {
    $this->dbConnection = $db_connection;
    $this->uuidGenerator = $uuid_generator;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->currentUser = $current_user;
  }

  /**
   * Get query for data from main tables.
   *
   * @param string $state
   *   State.
   * @param string $zip
   *   Zip.
   * @param $id_start
   *   Filter by id >= this.
   * @param $length
   *   Return a maximum of this many rows.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  private function getDataQuery($state, $zip, $id_start, $length) {
    $query = $this->dbConnection
      ->select('ham_station', 'hs')
      ->fields('hs', ['id', 'callsign', 'first_name', 'last_name', 'organization', 'operator_class'])
      ->fields('ha', ['address__address_line1', 'address__locality', 'address__administrative_area', 'address__postal_code'])
      ->fields('hl', ['latitude', 'longitude'])
      ->condition('hs.id', $id_start, '>=')
      ->range(0, $length)
      ->orderBy('hs.id');

    $query->join('ham_address', 'ha', 'ha.hash = hs.address_hash');
    $query->join('ham_location', 'hl', 'hl.id = ha.location_id');

    if (!empty($state)) {
      $query->condition('ha.address__administrative_area', $state);
    }

    if (!empty($zip)) {
      $query->condition('ha.address__postal_code', $zip . '%', 'LIKE');
    }

    return $query;
  }

  /**
   * Get data count.
   *
   * @param string $state
   *   State.
   * @param string $zip
   *   Zip.
   *
   * @return int
   *   Count of rows.
   */
  private function getDataCount($state, $zip) {
    // This gets the total number of stations within the selection location
    // regardless of whether they have been successfully geocoded or not. This
    // is done for performance and will result in a greater number than is
    // actually exported. This just means that the batch API progress bar will
    // not quite get to the end before processing is complete.
    $query = $this->dbConnection->select('ham_station', 'hs');
    $query->join('ham_address', 'ha', 'ha.hash = hs.address_hash');

    if (!empty($state)) {
      $query->condition('ha.address__administrative_area', $state);
    }

    if (!empty($zip)) {
      $query->condition('ha.address__postal_code', $zip . '%', 'LIKE');
    }

    $stmt = $query->countQuery()->execute();
    return $stmt->fetchCol()[0];
  }

  /**
   * Insert a row in the export table.
   *
   * @param string $batch_uuid
   *   Batch uuid.
   * @param int $timestamp
   *   Timestamp.
   * @param object $row
   *   StdClass of data from PDO result.
   */
  private function insertExportRow($batch_uuid, $timestamp, $row) {
    $this->dbConnection->insert(self::EXPORT_TABLE)
      ->fields([
        'batch_uuid' => $batch_uuid,
        'id' => $row->id,
        'callsign' => $row->callsign,
        'first_name' => $row->first_name,
        'last_name' => $row->organization ?: $row->last_name,
        'address' => $row->address__address_line1,
        'city' => $row->address__locality,
        'state' => $row->address__administrative_area,
        'zip' => $row->address__postal_code,
        'operator_class' => $row->operator_class,
        'is_club' => !empty($row->organization) ? 1 : 0,
        'latitude' => $row->latitude,
        'longitude' => $row->longitude,
        'timestamp' => $timestamp,
      ])
      ->execute();
  }

  /**
   * Get query for export table.
   *
   * @param string $batch_uuid
   *   Batch uuid.
   * @param $sort_order_start
   * Filter by sort_order >= this.
   * @param int $length
   *   Return a maximum of this many rows.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  private function getExportQuery($batch_uuid, $sort_order_start, $length = NULL) {
    $query = $this->dbConnection
      ->select(self::EXPORT_TABLE, 'ex')
      ->fields('ex', ['callsign', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'operator_class', 'is_club', 'latitude', 'longitude', 'sort_order'])
      ->condition('ex.batch_uuid', $batch_uuid)
      ->condition('ex.sort_order', $sort_order_start, '>=')
      ->orderBy('ex.sort_order');

    if (!empty($length)) {
      $query->range(0, $length);
    }

    return $query;
  }

  /**
   * Get count of rows in export table.
   *
   * @param string $batch_uuid
   *   Batch uuid.
   *
   * @return int
   *   Count of rows.
   */
  private function getExportCount($batch_uuid) {
    return $this->getExportQuery($batch_uuid, 0)
      ->countQuery()
      ->execute()
      ->fetchCol()[0];
  }

  /**
   * Delete data in export table.
   *
   * @param string $batch_uuid
   *   Batch uuid.
   */
  private function deleteExportData($batch_uuid) {
    $this->dbConnection
      ->delete(self::EXPORT_TABLE)
      ->condition('batch_uuid', $batch_uuid)
      ->execute();
  }

  /**
   * Set the sort_order field of the export table.
   *
   * @param string $batch_uuid
   *   Batch uuid.
   */
  private function sortResult($batch_uuid) {
    // This lets the export to file operation query batches just by sort_order
    // and the final result with be sorted.
    $this->dbConnection->query('SET @sort_order := 0');
    $sql = '
      UPDATE ham_station_export
      SET sort_order = @sort_order := @sort_order + 1
      WHERE batch_uuid = :uuid
      ORDER by state, last_name, first_name, callsign, id';

    $this->dbConnection
      ->prepareQuery($sql)
      ->execute([':uuid' => $batch_uuid]);
  }

  /**
   * Export into table process callback.
   *
   * @param string $state
   *   State.
   * @param string $zip
   *   Zip.
   * @param array $context
   *   Context.
   */
  public function processBatch1($state, $zip, array &$context) {
    $state = strtoupper($state);
    if ($state == '**') {
      $state = '';
    }

    $sandbox = &$context['sandbox'];
    $results = &$context['results'];
    if (!isset($sandbox['timestamp'])) {
      $sandbox['timestamp'] = time();
      $sandbox['max_count'] = $this->getDataCount($state, $zip);
      $sandbox['done_count'] = 0;
      $sandbox['last_id'] = 0;
      $results = [
        'batch_uuid' => $this->uuidGenerator->generate(),
        'state' => $state,
        'zip' => $zip,
      ];
    }

    $data = $this->getDataQuery($state, $zip, $sandbox['last_id'] + 1, self::BATCH_SIZE)
      ->execute()
      ->fetchAll();

    if (!empty($data)) {
      foreach ($data as $row) {
        $this->insertExportRow($context['results']['batch_uuid'], $sandbox['timestamp'], $row);
      }

      $last_row = end($data);
      $sandbox['last_id'] = $last_row->id;
    }

    $batch_count = count($data);
    $done_count = $sandbox['done_count'] + $batch_count;
    $sandbox['done_count'] = $done_count;

    $context['message'] = $this->t('@count of @total stations exported to temporary table', [
      '@count' => $done_count,
      '@total' => $sandbox['max_count'],
    ]);

    if ($batch_count < self::BATCH_SIZE) {
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = $done_count / $sandbox['max_count'];
    }
  }

  /**
   * Get file uri.
   *
   * @param string $batch_uuid
   *   Uuid.
   *
   * @return string
   *   File uri.
   */
  private function getFileUri($batch_uuid) {
    return "private://ham-station-exports/{$batch_uuid}.csv";
  }

  /**
   * Open CSV file for writing.
   *
   * @param string $batch_uuid
   *   Batch uuid.
   *
   * @return \League\Csv\Writer
   */
  private function openFile($batch_uuid) {
    $uri = $this->getFileUri($batch_uuid);
    $dir = dirname($uri);
    $this->fileSystem->prepareDirectory($dir, FileSystem::CREATE_DIRECTORY | FileSystem::MODIFY_PERMISSIONS);
    return Writer::createFromPath($this->getFileUri($batch_uuid), 'a');
  }

  /**
   * Create file entity.
   *
   * @param string $batch_uuid
   *   Batch uuid.
   * @param string $state
   *   State.
   * @param string $zip
   *   Zip.
   *
   * @return string
   *   File uuid.
   */
  private function createFileEntity($batch_uuid, $state, $zip) {
    $file_name_parts = ['ham-stations'];

    if (!empty($state)) {
      $file_name_parts[] = $state;
    }
    if (!empty($zip)) {
      $file_name_parts[] = $zip;
    }

    $download_file_name = strtolower(implode('-', $file_name_parts)) . '.csv';

    $file_entity = $this->entityTypeManager
      ->getStorage('file')
      ->create([
        'filename' => $download_file_name,
        'uri' => $this->getFileUri($batch_uuid),
        'uid' => $this->currentUser->id(),
      ]);

    $file_entity->save();
    return $file_entity->uuid();
  }

  /**
   * Export into file process callback.
   *
   * @param string $delimiter
   *   Delimiter.
   * @param string $enclosure
   *   Enclosure.
   * @param array $context
   *   Context.
   */
  public function processBatch2($delimiter, $enclosure, array &$context) {
    $sandbox = &$context['sandbox'];
    $results = &$context['results'];
    $file_writer = $this->openFile($results['batch_uuid']);
    $file_writer->setDelimiter($delimiter);
    $file_writer->setEnclosure($enclosure);

    if (!isset($results['done_count'])) {
      $this->sortResult($results['batch_uuid']);
      $results['done_count'] = 0;
      $sandbox['total_count'] = $this->getExportCount($results['batch_uuid']);
      $sandbox['last_sort_order'] = 0;
      $sandbox['done_header'] = FALSE;
    }

    $data = $this->getExportQuery($results['batch_uuid'], $sandbox['last_sort_order'] + 1, self::BATCH_SIZE)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    if (!empty($data)) {
      if (!$sandbox['done_header']) {
        $new_data = $data[0];
        unset($new_data['sort_order']);
        $file_writer->insertOne(array_keys($new_data));
        $sandbox['done_header'] = TRUE;
      }

      $new_data = array_map(function ($row) {
        unset($row['sort_order']);
        return $row;
      }, $data);

      $file_writer->insertAll($new_data);

      $last_row = end($data);
      $sandbox['last_sort_order'] = $last_row['sort_order'];
    }

    $batch_count = count($data);
    $done_count = $results['done_count'] + $batch_count;
    $results['done_count'] = $done_count;

    $context['message'] = $this->t('@count of @total stations exported to file', [
      '@count' => $done_count,
      '@total' => $sandbox['total_count'],
    ]);

    if ($batch_count < self::BATCH_SIZE) {
      $this->deleteExportData($results['batch_uuid']);
      $results['file_uuid'] = $this->createFileEntity($results['batch_uuid'], $results['state'], $results['zip']);
      $context['finished'] = 1;
    }
    else {
      $context['finished'] = $done_count / $sandbox['total_count'];
    }
  }

    public function finishedBatch($success, array $results, array $operations) {
    if ($success) {
      $this->messenger->addMessage(
        $this->t('@count stations with geocoded locations exported to file.', [
          '@count' => $results['done_count'],
        ])
      );

      return new RedirectResponse(Url::fromRoute('ham_station.map_export', [], [
        'query' => ['file' => $results['file_uuid']]
      ])->toString());
    }
    else {
      $this->messenger->addError($this->t('An error occurred.'));
    }

    return NULL;
  }

}
