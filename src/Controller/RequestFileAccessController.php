<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;

class RequestFileAccessController extends ControllerBase {

  /**
   * Loads request-file-access webform
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function requestAccess(NodeInterface $node) {
    $values = [
      'data' => [
        'node_id' => $node->id(),
      ]
    ];
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('request_file_access');
    $webform = $webform->getSubmissionForm($values, 'add');
    return $webform;
  }

}
