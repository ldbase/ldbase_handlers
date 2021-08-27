<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Node\NodeInterface;

class ContactProjectController extends ControllerBase {

  /**
   * Loads the Contact Project webform
   *
   * THe Project Node passed into the form.
   * @param \Drupal\Node\NodeInterface $node
   */
  public function contactProject(NodeInterface $node) {
    // load form
    $node_id = $node->id();
    $values = [
      'data' => [
        'node_id' => $node_id,
      ],
    ];
    $operation = 'add';
    $webform = $this->entityTypeManager()->getStorage('webform')->load('ldbase_contact_form');
    $webform = $webform->getSubmissionForm($values, $operation);
    // change text on form
    $project_title = $node->getTitle();
    $form_introduction = $this->t('Use this form to contact the Project Administrators of Project: @title.  The message will be sent by email and will contain your email address so that they can respond to you.', ['@title' => $project_title]);
    $webform['elements']['form_introduction']['#markup'] = $form_introduction;

    return $webform;
  }

}
