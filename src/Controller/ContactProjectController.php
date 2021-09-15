<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Node\NodeInterface;
use Drupal\user_email_verification\UserEmailVerification;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContactProjectController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The User Email Verification service.
   *
   * @var \Drupal\user_email_verification\UserEmailVerification;
   */
  protected $userEmailVerification;

  /**
   * ContactProjectController Constructor
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The Current User
   * @param \Drupal\user_email_verification\UserEmailVerification $userEmailVerification
   *   User Email Verification Service
   */
  public function __construct(AccountInterface $currentUser, UserEmailVerification $userEmailVerification) {
    $this->currentUser = $currentUser;
    $this->userEmailVerification = $userEmailVerification;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('user_email_verification.service')
    );
  }

  /**
   * Loads the Contact Project webform
   *
   * THe Project Node passed into the form.
   * @param \Drupal\Node\NodeInterface $node
   */
  public function contactProject(NodeInterface $node) {
    // Are you trying to get around a do_not_contact flag?
    if ($node->field_do_not_contact->value) {
      $redirect_message = $this->t("You may not contact the administrators of this project in LDbase.");
      $this->messenger()->addWarning($redirect_message);

      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    }
    else {
      // Has the user verified their email address?
      $uid = $this->currentUser->id();
      if ($this->userEmailVerification->isVerificationNeeded($uid)) {
        // if not user, redirect to Person view with error message
        $redirect_message = $this->t("You must verify your email address to contact others. <a href='/user/user-email-verification'>Resend verification link</a>");
        $this->messenger()->addWarning($redirect_message);

        return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
      }
      else {
        // load form
        $subject_line = $this->t("LDbase user has question about Project: @title", ['@title' => $node->getTitle()]);
        $node_id = $node->id();
        $values = [
          'data' => [
            'subject' => $subject_line,
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
        $webform['elements']['subject']['#attributes']['readonly'] = 'readonly';

        return $webform;
      }
    }
  }

}
