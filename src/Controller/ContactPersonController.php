<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Node\NodeInterface;
use Drupal\user_email_verification\UserEmailVerification;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContactPersonController extends ControllerBase {

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
   * ContactPersonController Constructor
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
   * Loads Contact Person Webform.
   *
   * The Person node passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function contactPerson(NodeInterface $node) {
    // Has the user verified their email address?
    $uid = $this->currentUser->id();
    if ($this->userEmailVerification->isVerificationNeeded($uid)) {
      // if not user, redirect to Person view with error message
      $redirect_message = $this->t("You must verify your email address to contact others. <a href='/user/user-email-verification'>Resend verification link</a>");
      $this->messenger()->addWarning($redirect_message);

      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    }
    else {
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
        // if not user, redirect to Person view with message
        $redirect_message = $this->t("This LDbase Person cannot be messaged using the contact form.");
        $this->messenger()->addStatus($redirect_message);

        return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
      }
    }
  }

}
