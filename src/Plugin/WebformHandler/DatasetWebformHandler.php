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

    // hidden passed_id field
    $passed_id = $submission_array['passed_id'];

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
        'field_affiliated_parents' => $passed_id,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully.');
      //save the node
      $node->save();
      // get groupId of parent that was passed in - assumes Group Cardinality = 1
      $parent_node = Node::load($passed_id);
      $group_contents = GroupContent::loadByEntity($parent_node);
      foreach ($group_contents as $group_content) {
        $group = $group_content->getGroup();
      }
      // add this dataset to the parent's group
      $plugin_id = 'group_node:' . $node->getType();
      $group->addContent($node, $plugin_id);
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
      $form_state->set('redirect_message', $title . ' was updated successfully.');
      //save the node
      $node->save();
    }

    // add node id to form_state to be used for redirection
    $form_state->set('node_redirect', $node->id());
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // data collection end date cannot come before start date
    $this->validateDataCollectionDates($form_state);
    // validate participants
    $this->validateParticipants($form_state);
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

  /**
   * Validate Data Collection field
   * End date cannot come before start date
   */
  private function validateDataCollectionDates(FormStateInterface $form_state) {
    $data_collection_period = $form_state->getValue('data_collection_period');
    if (empty($data_collection_period)) {
      return;
    }
    else {
      foreach ($data_collection_period as $delta => $row_array) {
        if (strtotime($row_array['end_date']) <= strtotime($row_array['start_date'])) {
          $message = 'The data collection end date must be after the start date.';
          $form_state->setErrorByName('data_collection_period][items]['.$delta, $message);
        }
      }
    }
  }

  /**
   * Validate Participants Custom Composite
   * Age range "to" must be greater than "from"
   */
  private function validateParticipants(FormStateInterface $form_state) {
    $participants = $form_state->getValue('participants');
    if (empty($participants)) {
      return;
    }
    else {
      foreach ($participants as $delta => $row_array) {
        // if there is a row, make sure the type is selected
        if (empty($row_array['participant_type'])) {
          $type_message = "You must select a Participant Type.";
          $form_state->setErrorByName('participants][items]['.$delta.'][participant_type', $type_message);
        }
        // if there is a row, make sure there is a number of participants
        if (empty($row_array['number_of_participants'])) {
          $participants_message = "You must enter the number of participants.";
          $form_state->setErrorByName('participants][items]['.$delta.'][number_of_participants', $participants_message);
        }
        // age range "to" must be greater than "from"
        if (!empty($row_array['age_range_from'])
          && (empty($row_array['age_range_to']) || intval($row_array['age_range_to']) < intval($row_array['age_range_from']))
        ) {
          $age_range_message = "The participant Age Range To must be greater than the Age Range From.";
          $form_state->setErrorByName('participants][items]['.$delta.'][age_range_from', $age_range_message);
          $form_state->setErrorByName('participants][items]['.$delta.'][age_range_to');
        }
      }
    }
  }

 }
