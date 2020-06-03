<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Adds DOI to node
 *
 * @WebformHandler(
 *   id = "request_file_access_handler",
 *   label = @Translation("LDbase Request File Access Handler"),
 *   description = @Translation("Adds Group ID to File Access Request submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

class RequestFileAccessWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    $node = Node::load($submission_array['node_id']);
    $node_title = $node->getTitle();

    // Actually get real group ID later.
    $submission_array['group_id'] = 1;

    $webform_submission->setData($submission_array);
    $message = "Your request for access to <a href='/node/{$node->id()}' target='_blank'>{$node_title}</a> was successfully created.";
    $form_state->set('redirect_message', $message);
    $form_state->set('node_redirect', $node->id());
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // redirect to node view
    $route_name = 'entity.node.canonical';
    $route_parameters = ['node' => $form_state->get('node_redirect')];
    if ($redirect_message = $form_state->get('redirect_message')) {
      $this->messenger()->addStatus($this->t($redirect_message));
    }
    $form_state->setRedirect($route_name, $route_parameters);
  }

}
