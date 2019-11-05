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
    $field_location = [
      'locality' => $submission_array['location']['city'],
      'administrative_area' =>$submission_array['location']['state_province'],
      'country_code' => $submission_array['location']['country'],
    ];

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'organization',
        'status' => TRUE, // published
        'title' => $title,
        'body' => $body,
        'field_website' => $field_website,
        'field_location' => $field_location,
      ]);
    }
    else {
      // update node
      $node = Node::load($nid);
      $node->set('title', $title);
      $node->set('body', $body);
      $node->set('field_website', $field_website);
      $node->set('field_location', $field_location);
    }

  //save the node
  $node->save();
  }
 }
