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
 *   id = "attach_doi",
 *   label = @Translation("LDbase DOI Confirmation"),
 *   category = @Translation("DOI"),
 *   description = @Translation("Mints DOI and saves it to a node."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

class DoiWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    $node = Node::load($submission_array['node_id']);

    // if "Yes" then mint DOI and attach to node
    if ($submission_array['create_doi'] == "Yes") {
      $doi = \Drupal::service('doi_crossref.minter.crossrefdois')->mint($node);
      $node->set('field_doi', $doi);
      // save the node
      $node->save();
      $doi_link = Url::fromUri('http://doi.org/' . $doi)->toString();
      $message = 'The DOI <a href="' . $doi_link . '" target="_blank">' . $doi . '</a> was successfully created';
      $form_state->set('redirect_message', $message);
    }
    // add node id to form_state to be used for redirection
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
