<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Create and send email messages to other LDbase users or project administrators
 *
 * @WebformHandler(
 *   id = "ldbase_contact_handler",
 *   label = @Translation("LDbase Contact Form Handler"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and sends messages to other LDbase users or to project administrators"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class LDbaseContactFormHandler extends WebformHandlerBase {

  /**
   * The EntityTypeManager
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The LDbase Object Service
   *
   * @var \Drupal\ldbase_content\LDbaseObjectService
   */
  protected $ldbaseObjectService;

  /**
   * The LDbase message service.
   *
   * @var \Drupal\ldbase_handlers\LDbaseMessageService
   */
  protected $ldbaseMessageService;

  /**
   * Message notifier service
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $notifier;

  /**
   * The User Email Verification service
   *
   * @var \Drupal\user_email_verification\UserEmailVerification
   */
  protected $userEmailVerificationService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->ldbaseObjectService = $container->get('ldbase.object_service');
    $instance->ldbaseMessageService = $container->get('ldbase_handlers.message_service');
    $instance->notifier = $container->get('message_notify.sender');
    $instance->userEmailVerificationService = $container->get('user_email_verification.service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    $node = $this->entityTypeManager->getStorage('node')->load($submission_array['node_id']);
    $message_template = 'ldbase_contact_form';
    $message_uid = $this->currentUser->id(); //sender
    $message_subject = $submission_array['subject'];
    $message_body = $submission_array['message'];

    // contact person
    if ($node->bundle() == 'person') {
      $field_to_users = $node->field_drupal_account_id->target_id;
    }
    // contact project
    if ($node->bundle() == 'project') {
      $groupRoles = ['project_group-administrator'];
      $field_to_users = $this->ldbaseMessageService->getGroupUserIdsByRoles($node,$groupRoles);
      $group_admin_emails = [];
      foreach ($field_to_users as $admin_id) {
        $admin = $this->entityTypeManager->getStorage('user')->load($admin_id);
        $group_admin_emails[] = $admin->mail->value;
      }
      $email_list = implode(',', $group_admin_emails);
    }

    $message = $this->entityTypeManager->getStorage('message')
      ->create(['template' => $message_template, 'uid' => $message_uid]);
    $message->set('field_from_user', $message_uid);
    $message->set('field_to_users', $field_to_users);
    $message->setArguments([
      '@subject' => $message_subject,
      '@message' => $message_body,
    ]);

    $message->save();

    //TODO
    // send email

  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // redirect to node view
    $route_name = 'entity.node.canonical';
    $submission_array = $webform_submission->getData();
    $route_parameters = ['node' => $submission_array['node_id']];

    $this->messenger()->addStatus($this->t('Your message has been sent.'));

    $form_state->setRedirect($route_name, $route_parameters);
  }

  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    $node = $this->entityTypeManager->getStorage('node')->load($submission_array['node_id']);
    // check that id hasn't been changed
    $original_uuid = $webform_submission->getSourceUrl()->getRouteParameters()['node'];
    $original_node = $this->ldbaseObjectService->getLdbaseObjectFromUuid($original_uuid);
    $default_error_message = 'An invalid id was passed to the form.';
    if ($original_node->id() != $node->id()) {
      $this->messenger()->addError($this->t($default_error_message));
      $form_state->setErrorByName('form_introduction','');
    }
    // these checks shouldn't be needed but just in case
    if ($node->bundle() == 'person') {
      if (!$node->field_drupal_account_id->target_id) {
        $this->messenger()->addError($this->t($default_error_message));
        $form_state->setErrorByName('form_introduction','');
      }
      else {
        // is this an active user account
        $to_user = $this->entityTypeManager->getStorage('user')->load($node->field_drupal_account_id->target_id);
        if (!$to_user->status->value) {
          $this->messenger()->addError($this->t($default_error_message));
          $form_state->setErrorByName('form_introduction','');
        }
      }
    }
    // if it's not a person then it has to be a project
    elseif ($node->bundle() != 'project') {
      $this->messenger()->addError($this->t($default_error_message));
      $form_state->setErrorByName('form_introduction','');
    }
  }

 }
