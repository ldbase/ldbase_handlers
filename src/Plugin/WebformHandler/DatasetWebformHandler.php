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
 * Create and edit Dataset nodes from a webform submission
 *
 * @WebformHandler(
 *   id = "dataset_from_webform",
 *   label = @Translation("LDbase Dataset"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and updates Dataset content nodes from Webform Submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class DatasetWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    // Get the submitted form values
    $submission_array = $webform_submission->getData();
    $nid = $submission_array['node_id'];
    $title = $submission_array['title'];
    $field_doi = $submission_array['doi'];
    $body = [
      'value' => $submission_array['description'],
      'format' => 'basic_html',
    ];
    $field_related_persons = $submission_array['contributors'];
    $field_related_organizations = $submission_array['host_organizations'];
    $field_location = $submission_array['location'];
    $field_component_skills = $submission_array['constructs'];
    $field_time_points = $submission_array['time_points'];

    $data_collection_period = $submission_array['data_collection_period'];
    if (!empty($data_collection_period)) {
      foreach ($data_collection_period as $key => $value) {
        $field_data_collection_period[$key]['value'] = $value['start_date'];
        $field_data_collection_period[$key]['end_value'] = $value['end_date'];
      }
    }
    else {
      $field_data_collection_period = [];
    }

    $field_data_collection_locations = $submission_array['data_collection_locations'];
    $field_assessment_name = $submission_array['assessment_name'];

    // demographics paragraph
    $demographics_array = $submission_array['participants'];
    if (!empty($demographics_array)) {
      foreach ($demographics_array as $key => $value) {
        $field_age_range['from'] = $value['age_range_from'];
        $field_age_range['to'] = $value['age_range_to'];
        $demographic_data[$key] = Paragraph::create([
          'type' => 'demographics',
          'field_age_range' => $field_age_range,
          'field_number_of_participants' => $value['number_of_participants'],
          'field_participant_type' => $value['participant_type'],
        ]);
        $demographic_data[$key]->save();
        $field_demographics_information[$key] = [
          'target_id' => $demographic_data[$key]->id(),
          'target_revision_id' => $demographic_data[$key]->getRevisionId(),
        ];
      }
    }
    else {
      $field_demographics_information = [];
    }

    $field_special_populations = $submission_array['special_populations'];
    $field_variable_types_in_dataset = $submission_array['variable_types_in_dataset'];

    if (!empty($submission_array['license'])) {
      $field_license = $submission_array['license'];
    }
    else {
      $field_license = [];
    }

    // file access restrictions paragraph
    $file_access_array = $submission_array['file_access_restrictions'];
    if (!empty($file_access_array)) {
      foreach ($file_access_array as $key => $value) {
        $access_data[$key] = Paragraph::create([
          'type' => 'file_access_restrictions',
          'field_file_embargoed' => $value['file_embargoed'] == 'Yes' ? 1 : 0,
          'field_embaro_expiry_date' => date($value['embargo_expiry_date']),
          'field_allow_file_requests' => $value['allow_file_requests'] == 'Yes' ? 1 : 0,
        ]);
        $access_data[$key]->save();
        $field_file_access_restrictions[$key] = [
          'target_id' => $access_data[$key]->id(),
          'target_revision_id' => $access_data[$key]->getRevisionId(),
        ];
      }
    }
    else {
       $field_file_access_restrictions = [];
    }

    $field_external_resource = $submission_array['external_resource'];

    // file metadata paragraph
    $files_array = $submission_array['file'];
    if (!empty($files_array)) {
      foreach ($files_array as $key => $value) {
        $file_id = $files_array[$key]['file_upload'];
        if (!empty($file_id)) {
          $file = \Drupal\file\Entity\File::load($file_id);
          $path = $file->getFileUri();
          $data = file_get_contents($path);
          $paragraph_file = file_save_data($data, 'public://' . $file->getFilename(), FILE_EXISTS_RENAME);
          $paragraph_file_id = $paragraph_file->id();
        }
        else {
          $paragraph_file_id = NULL;
        }
        $paragraph_data[$key] = Paragraph::create([
          'type' => 'file_metadata',
          'field_file_format' => $value['file_format'],
          'field_file_upload' => $paragraph_file_id,
          'field_format_version' => $value['format_version'],
          'field_file_version_description' => $value['file_version_description'],
        ]);
        $paragraph_data[$key]->save();
        $field_file[$key] = [
          'target_id' => $paragraph_data[$key]->id(),
          'target_revision_id' => $paragraph_data[$key]->getRevisionId(),
        ];
      }
    }
    else {
      $field_file = [];
    }

    // publication information paragraph
    $publications_array = $submission_array['publication_info'];
    if (!empty($publications_array)) {
      foreach ($publications_array as $key => $value) {
        $paragraph_data[$key] = Paragraph::create([
          'type' => 'publication_metadata',
          'field_publication_date' => $value['publication_date'],
          'field_publication_source' => $value['publication_source'],
        ]);
        $paragraph_data[$key]->save();
        $field_publication_info[$key] = [
          'target_id' => $paragraph_data[$key]->id(),
          'target_revision_id' => $paragraph_data[$key]->getRevisionId(),
        ];
      }
    }
    else {
      $field_publication_info = [];
    }

    $field_affiliated_code = $submission_array['affiliated_code'];
    $field_affiliated_documents = $submission_array['affiliated_documents'];
    $field_unaffiliated_citation = $submission_array['unaffiliated_citation'];

    // hidden project_id field
    $hidden_project_id = $submission_array['project_id'];

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'dataset',
        'status' => TRUE, // published
        'title' => $title,
        'field_doi' => $field_doi,
        'body' => $body,
        'field_related_persons' => $field_related_persons,
        'field_related_organizations' => $field_related_organizations,
        'field_location' => $field_location,
        'field_component_skills' => $field_component_skills,
        'field_time_points' => $field_time_points,
        'field_data_collection_period' => $field_data_collection_period,
        'field_data_collection_locations' => $field_data_collection_locations,
        'field_assessment_name' => $field_assessment_name,
        'field_demographics_information' => $field_demographics_information,
        'field_special_populations' => $field_special_populations,
        'field_variable_types_in_dataset' => $field_variable_types_in_dataset,
        'field_license' => $field_license,
        'field_file_access_restrictions' => $field_file_access_restrictions,
        'field_external_resource' => $field_external_resource,
        'field_file' => $field_file,
        'field_publication_info' => $field_publication_info,
        'field_affiliated_code' => $field_affiliated_code,
        'field_affiliated_documents' => $field_affiliated_documents,
        'field_unaffiliated_citation' => $field_unaffiliated_citation,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully.');
    }
    else {
      // update node
      $node = Node::load($nid);
      $node->set('title', $title);
      $node->set('field_doi', $field_doi);
      $node->set('body', $body);
      $node->set('field_related_persons', $field_related_persons);
      $node->set('field_related_organizations', $field_related_organizations);
      $node->set('field_location', $field_location);
      $node->set('field_component_skills', $field_component_skills);
      $node->set('field_time_points', $field_time_points);
      $node->set('field_data_collection_period', $field_data_collection_period);
      $node->set('field_data_collection_locations', $field_data_collection_locations);
      $node->set('field_assessment_name', $field_assessment_name);
      $node->set('field_demographics_information', $field_demographics_information);
      $node->set('field_special_populations', $field_special_populations);
      $node->set('field_variable_types_in_dataset', $field_variable_types_in_dataset);
      $node->set('field_license', $field_license);
      $node->set('field_file_access_restrictions', $field_file_access_restrictions);
      $node->set('field_external_resource', $field_external_resource);
      $node->set('field_file', $field_file);
      $node->set('field_publication_info', $field_publication_info);
      $node->set('field_affiliated_code', $field_affiliated_code);
      $node->set('field_affiliated_documents', $field_affiliated_documents);
      $node->set('field_unaffiliated_citation', $field_unaffiliated_citation);
      $form_state->set('redirect_message', $title . ' was updated successfully.');
    }

    //save the node
    $node->save();
    // add node id to form_state to be used for redirection
    $dataset_id = $node->id();
    $form_state->set('node_redirect', $dataset_id);

    // add dataset to project
    if ($hidden_project_id) {
      $project_node = Node::load($hidden_project_id);
      $project_datasets = $project_node->get('field_affiliated_datasets')->getValue();
      array_push($project_datasets, $dataset_id);
      $project_node->set('field_affiliated_datasets', $project_datasets);
      $project_node->save();
    }
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
