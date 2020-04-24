<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;

class DoiController extends ControllerBase {

  /**
   * Loads confirm_doi_creation confirmation webform
   *
   * created/updated node
   * @param \Drupal\Node\NodeInterface $node
   */
  public function confirmCreation(NodeInterface $node) {
    $values = [
      'data' => [
        'node_id' => $node->id(),
      ]
    ];
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('confirm_doi_creation');
    $webform = $webform->getSubmissionForm($values, 'add');
    return $webform;
  }

}
