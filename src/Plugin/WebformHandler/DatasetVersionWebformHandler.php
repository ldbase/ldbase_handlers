<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Create and edit Dataset nodes from a webform submission
 *
 * @WebformHandler(
 *   id = "edit_dataset_version_handler",
 *   label = @Translation("LDbase Edit Dataset Version"),
 *   category = @Translation("Content"),
 *   description = @Translation("Updates Dataset version Paragraph from Webform Submission"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class DatasetVersionWebformHandler extends WebformHandlerBase {
  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    // Get the submitted form values
    $submission_array = $webform_submission->getData();
    $version = reset($submission_array['dataset_version']);

    $paragraph = Paragraph::load($version['dataset_version_target_id']);
    $paragraph->set('field_file_format', $version['dataset_version_format']);
    //$paragraph->set('field_file_version_label', $version['dataset_version_label']);
    $paragraph->set('field_file_version_description', $version['dataset_version_description']);
    $paragraph->save();

    $parent_node = Node::load($paragraph->parent_id->value);

    // add node id to form_state to be used for redirection
    $redirect_parameters = [
      'uuid' => $parent_node->uuid(),
      'node' => $parent_node->id(),
    ];
    $form_state->set('node_redirect', $redirect_parameters);
    $form_state->set('redirect_message', 'Dataset version was updated successfully.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // validate file format
    $this->validateFileFormat($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    $route_name = 'view.dataset_versions.all_versions';

    $route_parameters = $form_state->get('node_redirect');
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));

    $form_state->setRedirect($route_name, $route_parameters);
  }

  /**
   * Validate file format
   * Uploaded file must have a format
   */
  private function validateFileFormat(FormStateInterface $form_state) {
    $dataset_version = reset($form_state->getValue('dataset_version'));
    if (!empty($dataset_version['dataset_version_format'])) {
      return;
    }
    else {
      $message = 'You must select a version format.';
      $form_state->setErrorByName('dataset_version][items][0][dataset_version_format', $message);
    }
  }

}
