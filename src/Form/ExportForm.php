<?php

namespace Drupal\ham_station\Form;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ham_station\Exporters\ExportHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form to export data as CSV.
 */
class ExportForm extends FormBase {

  /**
   * Export helper.
   *
   * @var \Drupal\ham_station\Exporters\ExportHelper
   */
  protected $exportHelper;

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  private $entityRepository;

  /**
   * Export form constructor.
   *
   * @param \Drupal\ham_station\Exporters\ExportHelper $export_helper
   *   Export helper.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository.
   */
  public function __construct(ExportHelper $export_helper, RequestStack $request_stack, EntityRepositoryInterface $entity_repository) {
    $this->exportHelper = $export_helper;
    $this->entityRepository = $entity_repository;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ham_station.export_service'),
      $container->get('request_stack'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'ham_station.export';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->buildDownload($form);

    $form['state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#description' => $this->t('Optionally enter a two letter US state abbreviation.'),
      '#attributes' => ['size' => 2, 'maxlength' => 2],
    ];

    $form['zip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zip'),
      '#description' => $this->t('Optionally entity a five digit zip code.'),
      '#attributes' => ['size' => 5, 'maxlength' => 5],
    ];

    $form['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('The single delimiter character to be used between values.'),
      '#default_value' => ',',
      '#attributes' => ['size' => 1, 'maxlength' => 1],
      '#required' => TRUE,
    ];

    $form['enclosure'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enclosure'),
      '#description' => $this->t('The single enclosure character to wrap values in case they contain the delimiter.'),
      '#default_value' => '"',
      '#attributes' => ['size' => 1, 'maxlength' => 1],
      '#required' => TRUE,
    ];

    $form['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#attributes' => ['class' => ['btn btn-primary']],
    ];

    return $form;
  }

  /**
   * Build download title and link.
   *
   * @param array $form
   *   Form array.
   *
   * @return array
   *   Form array.
   */
  private function buildDownload(array $form) {
    $file_uuid = $this->getRequest()->query->get('file');
    if (empty($file_uuid)) {
      return $form;
    }

    /** @var \Drupal\file\Entity\File $file_entity */
    $file_entity = $this->entityRepository->loadEntityByUuid('file', $file_uuid);
    if (empty($file_entity)) {
      return $form;
    }

    $form['download'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Download file'),
    ];

    $form['download']['link'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('<a href=":url">Click here</a> to download the generated file.', [
        ':url' => $file_entity->createFileUrl(),
      ]),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $state = trim($form_state->getValue('state'));
    $zip = trim($form_state->getValue('zip'));

    if (empty($state) && empty($zip)) {
      $form_state->setError($form['state'], $this->t('Both state and zip are blank which means this will download the whole database of about 800,000 stations. If you really want to do this then enter two asterisks (**) for state. This will take some time to process.'));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'operations' => [
        [
          [$this, 'processBatch1'], [
            $form_state->getValue('state'),
            $form_state->getValue('zip'),
          ],
        ],
        [
          [$this, 'processBatch2'], [
            $form_state->getValue('delimiter'),
            $form_state->getValue('enclosure'),
          ],
        ],
      ],
      'finished' => [$this, 'finishedBatch'],
      'title' => $this->t('Exporting to file'),
      'init_message' => $this->t('Counting…'),
      'progress_message' => $this->t('Exporting…'),
    ];

    batch_set($batch);
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
    $this->exportHelper->processBatch1($state, $zip, $context);
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
    $this->exportHelper->processBatch2($delimiter, $enclosure, $context);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   True for success.
   * @param array $results
   *   Results.
   * @param array $operations
   *   Operations.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   Redirect response.
   */
  public function finishedBatch($success, array $results, array $operations) {
    return $this->exportHelper->finishedBatch($success, $results, $operations);
  }

}
