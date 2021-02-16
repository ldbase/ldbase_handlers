<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Create and edit Organization nodes from a webform submission
 *
 * @WebformHandler(
 *   id = "org_from_webform",
 *   label = @Translation("LDbase Organization"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and updates Organization content nodes from Webform Submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class OrganizationWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    // Get the submitted form values
    $submission_array = $webform_submission->getData();

    $nid = $submission_array['node_id'];
    $title = $submission_array['name'];
    $body = [
      'value' => $submission_array['description'],
      'format' => 'basic_html',
    ];
    $field_website = $submission_array['website'];
    $field_location = $submission_array['location'];

    // Get image upload, save to public files, attach to node.
    $image_fid = $submission_array['image'];
    if (!empty($image_fid)) {
      //$new_fid = \Drupal::service('ldbase.webform_file_storage_service')->transferWebformFile($image_fid, 'organization');
      $field_thumbnail = [
        'target_id' => $image_fid,
        'alt' => 'Thumbnail for ' . $title,
        'title' => $title,
      ];
    }
    else {
      $field_thumbnail = NULL;
    }

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'organization',
        'status' => TRUE, // published
        'title' => $title,
        'body' => $body,
        'field_website' => $field_website,
        'field_location' => $field_location,
        'field_thumbnail' => $field_thumbnail,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully.');
    }
    else {
      // update node
      $node = Node::load($nid);
      $node->set('title', $title);
      $node->set('body', $body);
      $node->set('field_website', $field_website);
      $node->set('field_location', $field_location);
      $node->set('field_thumbnail', $field_thumbnail);
      $form_state->set('redirect_message', $title . ' was updated successfully.');
    }

    //save the node
    $node->save();
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
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));

    $form_state->setRedirect($route_name, $route_parameters);
  }

 }
