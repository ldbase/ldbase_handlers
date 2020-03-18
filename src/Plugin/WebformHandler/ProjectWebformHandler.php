<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Create and edit Project nodes from a webform submission
 *
 * @WebformHandler(
 *   id = "project_from_webform",
 *   label = @Translation("LDbase Project"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and updates Project content nodes from Webform Submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class ProjectWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    $submission_array = $webform_submission->getData();
    $nid = $submission_array['node_id'];
    $title = $submission_array['title'];
    $body = [
      'value' => $submission_array['description'],
      'format' => 'basic_html',
    ];
    $field_related_persons = $submission_array['related_persons'];
    $field_related_organizations = $submission_array['related_organizations'];
    $field_doi = $submission_array['doi'];
    $activity_range = $submission_array['activity_range'];
    foreach ($activity_range as $key => $value) {
      $field_activity_range[$key]['value'] = $value['start_date'];
      $field_activity_range[$key]['end_value'] = $value['end_date'];
    }
    $field_website = $submission_array['website'];
    // grant information paragraph
    foreach ($submission_array['grant_information'] as $key => $value) {
      $grant_data[$key] = Paragraph::create([
        'type' => 'grant_information',
        'field_funding_agency' => $value['funding_agency'],
        'field_grant_number' => $value['grant_number'],
      ]);
      $grant_data[$key]->save();
      $field_grant_information[$key] = [
        'target_id' => $grant_data[$key]->id(),
        'target_revision_id' => $grant_data[$key]->getRevisionId(),
      ];
    }
    $field_project_type = $submission_array['project_type'];
    $field_schooling = $submission_array['schooling'];
    $field_curricula = $submission_array['curricula'];
    $field_time_method = $submission_array['time_method'];
    $field_affiliated_datasets = $submission_array['affiliated_datasets'];
    $field_affiliated_documents = $submission_array['affiliated_documents'];
    $field_unaffiliated_citation = $submission_array['unaffiliated_citation'];

    if (!$nid) {
      //create node
      $node = Node::create([
        'type' => 'project',
        'status' => TRUE, // published
        'title' => $title,
        'body' => $body,
        'field_related_persons' => $field_related_persons,
        'field_related_organizations' => $field_related_organizations,
        'field_doi' => $field_doi,
        'field_activity_range' => $field_activity_range,
        'field_website' => $field_website,
        'field_grant_information' => $field_grant_information,
        'field_project_type' => $field_project_type,
        'field_schooling' => $field_schooling,
        'field_curricula' => $field_curricula,
        'field_time_method' => $field_time_method,
        'field_affiliated_datasets' => $field_affiliated_datasets,
        'field_affiliated_documents' => $field_affiliated_documents,
        'field_unaffiliated_citation' => $field_unaffiliated_citation,
      ]);
    }
    else {
      //update node
      $node = Node::load($nid);
      $node->set('title', $title);
      $node->set('body', $body);
      $node->set('field_related_persons', $field_related_persons);
      $node->set('field_related_organizations', $field_related_organizations);
      $node->set('field_doi', $field_doi);
      $node->set('field_activity_range', $field_activity_range);
      $node->set('field_website', $field_website);
      $node->set('field_grant_information', $field_grant_information);
      $node->set('field_project_type', $field_project_type);
      $node->set('field_schooling', $field_schooling);
      $node->set('field_curricula', $field_curricula);
      $node->set('field_time_method', $field_time_method);
      $node->set('field_affiliated_datasets', $field_affiliated_datasets);
      $node->set('field_affiliated_documents', $field_affiliated_documents);
      $node->set('field_unaffiliated_citation', $field_unaffiliated_citation);
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

    $form_state->setRedirect($route_name, $route_parameters);
  }

 }
