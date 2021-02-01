<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Create and send reported problem messages
 *
 * @WebformHandler(
 *   id = "report_a_problem",
 *   label = @Translation("LDbase Report a Problem"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and sends messages from the Report a Problem form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class ReportProblemWebformHandler extends WebformHandlerBase {
   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    $name = $submission_array['name'];
    $email = $submission_array['email'];
    $reply_requested = $submission_array['reply_requested'] ? 'Yes' : 'No';
    $issue = $submission_array['issue'];
    $url = $submission_array['url'];

    $message_data = [
      'name' => $name,
      'email' => $email,
      'reply_requested' => $reply_requested,
      'issue' => $issue,
      'url' => $url,
    ];

    \Drupal::service('ldbase_handlers.message_service')->reportProblemMessage($message_data);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // redirect to home page
    $route_name = 'ldbase.home';
    $this->messenger()->addStatus('Your message has been submitted.');
    $form_state->setRedirect($route_name);
  }

 }
