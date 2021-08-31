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
   * The Drupal MailManager
   *
   * @var Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->ldbaseObjectService = $container->get('ldbase.object_service');
    $instance->ldbaseMessageService = $container->get('ldbase_handlers.message_service');
    $instance->mailManager = $container->get('plugin.manager.mail');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    $node = $this->entityTypeManager->getStorage('node')->load($submission_array['node_id']);
    $message_template = 'ldbase_contact_form';
    $field_from_user = $this->currentUser->id();
    $from_email = $this->currentUser->getEmail();
    $message_subject = $submission_array['subject'];
    $message_body = $submission_array['message'];

    // contact person
    if ($node->bundle() == 'person') {
      $field_to_user = $node->field_drupal_account_id->target_id;
      $field_to_users = '';
      $message_uid = $field_to_user;
      $email_list = $node->field_email_value;

      $message = $this->entityTypeManager->getStorage('message')
        ->create(['template' => $message_template, 'uid' => $message_uid]);
      $message->set('field_from_user', $field_from_user);
      $message->set('field_to_user', $field_to_user);
      $message->set('field_to_users', $field_to_users);
      $message->setArguments([
        '@subject' => $message_subject,
        '@message' => $message_body,
      ]);
      $message->save();
    }
    // contact project
    if ($node->bundle() == 'project') {
      $groupRoles = ['project_group-administrator'];
      $field_to_users = $this->ldbaseMessageService->getGroupUserIdsByRoles($node,$groupRoles);
      $group_admin_emails = [];
      // save a message for each, but email all at once
      foreach ($field_to_users as $admin_id) {
        $admin = $this->entityTypeManager->getStorage('user')->load($admin_id);
        $group_admin_emails[] = $admin->mail->value;

        $message = $this->entityTypeManager->getStorage('message')
          ->create(['template' => $message_template, 'uid' => $admin_id]);
        $message->set('field_from_user', $field_from_user);
        $message->set('field_to_user', $admin_id);
        $message->set('field_to_users', $field_to_users);
        $message->setArguments([
          '@subject' => $message_subject,
          '@message' => $message_body,
        ]);
        $message->save();
      }
      $email_list = implode(',', $group_admin_emails);
    }

    // send email
    $module = 'ldbase_handlers';
    $key = 'ldbase_contact_form';
    $to = $email_list;
    $reply = $from_email;
    $params['subject'] = 'LDbase Contact Message: ' . $message_subject;
    $params['body'] = $message_body;
    $langcode = $this->currentUser->getPreferredLangcode();
    $send = TRUE;

    $mail_result = $this->mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);

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
