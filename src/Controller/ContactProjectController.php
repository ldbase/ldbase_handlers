<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ContactProjectController Constructor
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The Current User
   * @param \Drupal\user_email_verification\UserEmailVerification $userEmailVerification
   *   User Email Verification Service
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(AccountInterface $currentUser, UserEmailVerification $userEmailVerification, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->userEmailVerification = $userEmailVerification;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('user_email_verification.service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Loads the Contact Project webform
   *
   * THe Project Node passed into the form.
   * @param \Drupal\Node\NodeInterface $node
   */
  public function contactProject(NodeInterface $node)  {
    // make sure this is a project node
    $project_node = $this->getRootProjectNode($node);

    // Are you trying to get around a do_not_contact flag?
    if ($project_node->field_do_not_contact->value) {
      $redirect_message = $this->t("You may not contact the administrators of this project in LDbase.");
      $this->messenger()->addWarning($redirect_message);

      return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
    } else {
      // Has the user verified their email address?
      $uid = $this->currentUser->id();
      if ($this->userEmailVerification->isVerificationNeeded($uid)) {
        // if not user, redirect to Person view with error message
        $redirect_message = $this->t("You must verify your email address to contact others. <a href='/user/user-email-verification'>Resend verification link</a>");
        $this->messenger()->addWarning($redirect_message);

        return $this->redirect('entity.node.canonical', ['node' => $node->id()]);
      } else {
        // load form
        $email_subject = "Message from LDbase User";
        // $subject_line = $this->t("LDbase user has question about Project: @title", ['@title' => $node->getTitle()]);
        $node_id = $project_node->id();
        $values = [
          'data' => [
            'subject' => $email_subject,
            'node_id' => $node_id,
          ],
        ];
        $operation = 'add';
        $webform = $this->entityTypeManager()->getStorage('webform')->load('ldbase_contact_form');
        $webform = $webform->getSubmissionForm($values, $operation);
        // change text on form
        $project_title = $project_node->getTitle();
        $form_introduction = $this->t('Use this form to contact the Project Administrators of Project: @title.  The message will be sent by email and will contain your email address so that they can respond to you.', ['@title' => $project_title]);
        $webform['elements']['form_introduction']['#markup'] = $form_introduction;
        $webform['elements']['subject']['#attributes']['readonly'] = 'readonly';
        $subject_description = $this->t('The subject for project messages is read-only.');
        $webform['elements']['subject']['#description'] = $subject_description;

        return $webform;
      }
    }
  }

    public function getRootProjectNode(NodeInterface $node) {
      $bundle = $node->bundle();
      if ($bundle == 'project') {
        $parent_project_node = $node;
      }
      else {
        $parent_node = $this->getObjectParent($node);
        $parent_project_node = $this->getRootProjectNode($parent_node);
      }
      return $parent_project_node;
    }

    public function getObjectParent($node) {
      $affiliated_parents = $node->field_affiliated_parents->getValue();
      $parent_nid = $affiliated_parents[0]['target_id'];

      return $this->entityTypeManager->getStorage('node')->load($parent_nid);
    }


}
