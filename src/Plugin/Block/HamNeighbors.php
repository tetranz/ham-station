<?php

namespace Drupal\ham_station\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ham_station\Form\HamNeighborsForm;

/**
 * Provides a 'HamNeighbors' block.
 *
 * @Block(
 *  id = "ham_neighbors",
 *  admin_label = @Translation("Ham neighbors"),
 * )
 */
class HamNeighbors extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm(HamNeighborsForm::class);

    return [
      '#theme' => 'ham_neighbors',
      '#form' => $form,
    ];
  }

}
