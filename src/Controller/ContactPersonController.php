<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Node\NodeInterface;

class ContactPersonController extends ControllerBase {

  /**
   * Loads Contact Person Webform.
   *
   * The Person node passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function contactPerson(NodeInterface $node) {
    //check the person has a user account
    if ($node->field_drupal_account_id->target_id) {
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
      $person_name = $node->getTitle();
      $form_introduction = $this->t('Use this form to contact @person.  The message will be sent by email and will contain your email address so that they can respond to you.', ['@person' => $person_name]);
      $webform['elements']['form_introduction']['#markup'] = $form_introduction;

      return $webform;
    }
    else {
      // if not user, redirect to Person view with error message
      $redirect_message = $this->t("This LDbase Person cannot be messaged using the contact form.");
      $this->messenger()->addError($redirect_message);

      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    }




  }
}
