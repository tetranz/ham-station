<?php

namespace Drupal\ham_station\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Amateur Radio Station edit forms.
 *
 * @ingroup ham_station
 */
class HamStationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\ham_station\Entity\HamStation */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Amateur Radio Station.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Amateur Radio Station.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.ham_station.canonical', ['ham_station' => $entity->id()]);
  }

}
