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

    // date range select paragraph
    $field_data_collection_range = [];
    foreach ($submission_array['data_collection_period'] as $key => $value) {
      $field_from_month = $value['start_month'];
      $field_from_year = $value['start_year'];
      $field_to_month = $value['end_month'];
      $field_to_year = $value['end_year'];
      $period_target_id = $value['period_target_id'];
      $period_target_revision_id = $value['period_target_revision_id'];

      if (empty($period_target_id)) {
        $date_range[$key] = Paragraph::create([
          'type' => 'date_range_selection',
          'field_from_month' => $field_from_month,
          'field_from_year' => $field_from_year,
          'field_to_month' => $field_to_month,
          'field_to_year' => $field_to_year,
        ]);
      }
      else {
        $date_range[$key] = Paragraph::load($period_target_id);
        $date_range[$key]->set('field_from_month', $field_from_month);
        $date_range[$key]->set('field_from_year', $field_from_year);
        $date_range[$key]->set('field_to_month', $field_to_month);
        $date_range[$key]->set('field_to_year', $field_to_year);
      }

      $date_range[$key]->save();
      $field_data_collection_range[$key] = [
        'target_id' => $date_range[$key]->id(),
        'target_revision_id' => $date_range[$key]->getRevisionId(),
      ];
    }

    $field_data_collection_locations = $submission_array['data_collection_locations'];
    $field_data_locations_other = $submission_array['data_collection_locations_other'];
    $field_assessment_name = $submission_array['assessment_name'];
    $field_assessment_names_other = $submission_array['assessment_names_other'];

    // demographics paragraph
    $demographics_array = $submission_array['participants'];
    if (!empty($demographics_array)) {
      foreach ($demographics_array as $key => $value) {
        $field_age_range['from'] = $value['age_range_from'];
        $field_age_range['to'] = $value['age_range_to'];
        $field_number_of_participants = $value['number_of_participants'];
        $field_participant_type = $value['participant_type'];
        $participants_target_id = $value['participants_target_id'];
        $participants_target_revision_id = $value['participants_target_revision_id'];

        if (empty($participants_target_id)) {
          $demographic_data[$key] = Paragraph::create([
            'type' => 'demographics',
            'field_age_range' => $field_age_range,
            'field_number_of_participants' => $field_number_of_participants,
            'field_participant_type' => $field_participant_type,
          ]);
        }
        else {
          $demographic_data[$key] = Paragraph::load($participants_target_id);
          $demographic_data[$key]->set('field_age_range', $field_age_range);
          $demographic_data[$key]->set('field_number_of_participants', $field_number_of_participants);
          $demographic_data[$key]->set('field_participant_type', $field_participant_type);
        }
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

    $field_dataset_upload_or_external = $submission_array['dataset_upload_or_external'];
    $field_external_resource = $submission_array['external_resource'];

    // publication information paragraph
    $publications_array = $submission_array['publication_info'];
    if (!empty($publications_array)) {
      foreach ($publications_array as $key => $value) {
        $publication_month = $value['publication_month'];
        $publication_year = $value['publication_year'];
        $publication_source = $value['publication_source'];
        $publication_target_id = $value['publication_target_id'];
        $publication_target_revision_id = $value['publication_target_revision_id'];

        if (empty($publication_target_id)) {
          $paragraph_data[$key] = Paragraph::create([
            'type' => 'publication_metadata',
            'field_publication_month' => $publication_month,
            'field_publication_year' => $publication_year,
            'field_publication_source' => $publication_source,
          ]);
        }
        else {
          $paragraph_data[$key] = Paragraph::load($publication_target_id);
          $paragraph_data[$key]->set('field_publication_month', $publication_month);
          $paragraph_data[$key]->set('field_publication_year', $publication_year);
          $paragraph_data[$key]->set('field_publication_source', $publication_source);
        }

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

    // file metadata paragraph
    $files_array = $submission_array['dataset_version'];
    if ($nid) {
      $field_dataset_version = $this->getExistingDatasetVersions($nid);
    }
    else {
      $field_dataset_version = [];
    }

    if (!empty($files_array)) {
      foreach ($files_array as $key => $composite) {

        if ($this->fileHasChanged($composite)) {
          $file_id = $files_array[$key]['dataset_version_upload'];
          $new_fid = \Drupal::service('ldbase.webform_file_storage_service')->transferWebformFile($file_id, 'dataset');
          $paragraph_file_id = $new_fid;

          if (empty($composite['dataset_version_id'])) {
            $dataset_version_id = 1;
          }
          else {
            $dataset_version_id = $composite['dataset_version_id'] + 1;
          }

          $paragraph_data = Paragraph::create([
            'type' => 'file_metadata',
            'field_file_format' => $composite['dataset_version_format'],
            'field_file_upload' => $paragraph_file_id,
            'field_file_version_id' => $dataset_version_id,
            'field_file_version_label' => $composite['dataset_version_label'],
            'field_file_version_description' => $composite['dataset_version_description'],
          ]);
          $paragraph_data->save();
          $new_paragraph = [
            'target_id' => $paragraph_data->id(),
            'target_revision_id' => $paragraph_data->getRevisionId(),
          ];
          array_push($field_dataset_version, $new_paragraph);
        }
        else { // file not changed
          $paragraph_data = Paragraph::load($composite['dataset_version_target_id']);
          $paragraph_data->set('field_file_format', $composite['dataset_version_format']);
          $paragraph_data->set('field_file_version_label', $composite['dataset_version_label']);
          $paragraph_data->set('field_file_version_description', $composite['dataset_version_description']);
          $paragraph_data->save();
        }
      }
    }
    else {
      /**
       * If user clears all dataset version fields on an update, just get existing
       * saved data.  Do not eliminate all versions
       */
      if ($nid) {
        $field_dataset_version = $this->getExistingDatasetVersions($nid);
      }
      else {
        $field_dataset_version = [];
      }
    }

    $embargoed = $submission_array['embargoed']; // 1 if embargoed, 0 if unembargoed
    $embargo_expiry = $submission_array['embargo_expiry']; // date if set, empty if not

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
        'field_data_collection_range' => $field_data_collection_range,
        'field_data_collection_locations' => $field_data_collection_locations,
        'field_data_locations_other' => $field_data_locations_other,
        'field_assessment_name' => $field_assessment_name,
        'field_assessment_names_other' => $field_assessment_names_other,
        'field_demographics_information' => $field_demographics_information,
        'field_special_populations' => $field_special_populations,
        'field_variable_types_in_dataset' => $field_variable_types_in_dataset,
        'field_license' => $field_license,
        'field_dataset_upload_or_external' => $field_dataset_upload_or_external,
        'field_external_resource' => $field_external_resource,
        'field_publication_info' => $field_publication_info,
        'field_dataset_version' => $field_dataset_version,
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
      $node->set('field_data_collection_range', $field_data_collection_range);
      $node->set('field_data_collection_locations', $field_data_collection_locations);
      $node->set('field_data_locations_other', $field_data_locations_other);
      $node->set('field_assessment_name', $field_assessment_name);
      $node->set('field_assessment_names_other', $field_assessment_names_other);
      $node->set('field_demographics_information', $field_demographics_information);
      $node->set('field_special_populations', $field_special_populations);
      $node->set('field_variable_types_in_dataset', $field_variable_types_in_dataset);
      $node->set('field_license', $field_license);
      $node->set('field_dataset_upload_or_external', $field_dataset_upload_or_external);
      $node->set('field_external_resource', $field_external_resource);
      $node->set('field_publication_info', $field_publication_info);
      $node->set('field_dataset_version', $field_dataset_version);
      $form_state->set('redirect_message', $title . ' was updated successfully.');
      //save the node
      $node->save();
    }
    // put new nid in form_state may be used for redirection
    $form_state->set('this_nid', $node->id());
    // add node uuid to form_state to be used for redirection
    $form_state->set('node_redirect', $node->uuid());
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // data collection end date cannot come before start date
    $this->validateDataCollectionDates($form_state);
    // validate participants
    $this->validateParticipants($form_state);
    // validate publication date
    $this->validatePublicationDate($form_state);
    // validate dataset file
    $this->validateDatasetFile($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    // if no DOI redirect to DOI creation confirmation
    if (empty($submission_array['doi'])) {
      $route_name = 'ldbase_handlers.confirm_doi_creation';
    }
    else {
      // redirect to node view
      $route_name = 'entity.node.canonical';
      $node_id = $form_state->get('this_nid');
      $form_state->set('node_redirect', $node_id);
    }
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
        // if start month then start year
        if (!empty($row_array['start_month']) && empty($row_array['start_year'])) {
          $message = 'If you select a data collection start month, then you must enter a start year.';
          $form_state->setErrorByName('data_collection_period][items]['.$delta.'][start_year', $message);
        }
        // if end month then end year
        if (!empty($row_array['end_month']) && empty($row_array['end_year'])) {
          $message = 'If you select a data collection end month, then you must enter an end year.';
          $form_state->setErrorByName('data_collection_period][items]['.$delta.'][end_year', $message);
        }
        // if end year
          // then must have start year
          // and end year must be >= to start year
          // if years are equal and months are not empty, then to_month >= from_month
        if (!empty($row_array['end_year'])) {
          if (empty($row_array['start_year'])) {
            $message = 'If you have a data collection end year, then you must enter a start year.';
            $form_state->setErrorByName('data_collection_period][items]['.$delta, $message);
          }
          elseif ($row_array['end_year'] < $row_array['start_year']) {
            $message = 'The data collection end year must be equal to or greater the start year.';
            $form_state->setErrorByName('data_collection_period][items]['.$delta, $message);
          }
          elseif (($row_array['end_year'] == $row_array['start_year'])
          && (!empty($row_array['end_month']) && !empty($row_array['start_month']))) {
            if ($row_array['end_month'] < $row_array['start_month']) {
              $message = 'The data collection end month and year must be later or equal to the start month and year.';
              $form_state->setErrorByName('data_collection_period][items]['.$delta, $message);
            }
          }
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

  /**
   * Validate Publication date
   * If month is selected, then year must be selected
   */
  private function validatePublicationDate(FormStateInterface $form_state) {
    $publications = $form_state->getValue('publication_info');
    if (empty($publications)) {
      return;
    }
    else {
      foreach ($publications as $delta => $row_array) {
        if (!empty($row_array['publication_month']) && empty($row_array['publication_year'])) {
          $message = 'If you select a publication month, then you must select a publication year.';
          $form_state->setErrorByName('publication_info][items]['.$delta.'][publication_year', $message);
        }
      }
    }
  }

  /**
   * Validate dataset file
   * If file is uploaded, then format must be selected
   * If format, version, or description are entered, then file must be uploaded
   */
  private function validateDatasetFile(FormStateInterface $form_state) {
    $dataset_version = $form_state->getValue('dataset_version');
    if (empty($dataset_version)) {
      if ($form_state->getValue('dataset_upload_or_external') === "upload") {
        $message = 'If you answer that the dataset will be uploaded, you must uplaod a file.';
        $form_state->setErrorByName('dataset_version][items][0][dataset_version_upload', $message);
      }
      else {
        return;
      }
    }
    else {
      foreach ($dataset_version as $delta => $row_array) {
        if (!empty($row_array['dataset_version_upload']) && empty($row_array['dataset_version_format'])) {
          $message = 'If you upload a dataset file, you must select a version format.';
          $form_state->setErrorByName('dataset_version][items]['.$delta.'][dataset_version_format', $message);
        }
        $some_data = !empty($row_array['dataset_version_format']) || !empty($row_array['dataset_version_label']) || !empty($row_array['dataset_version_description']);
        if ($some_data && empty($row_array['dataset_version_upload'])) {
          $message = 'If you enter dataset version information, you must uplaod a file.';
          $form_state->setErrorByName('dataset_version][items]['.$delta.'][dataset_version_upload', $message);
        }
      }
    }
  }

  /**
   * Check if file metadata paragraph has changes
   */
  private function fileHasChanged(array $submitted_values) {
    $currentFile = $submitted_values['dataset_version_upload'];
    $p = Paragraph::load($submitted_values['dataset_version_target_id']);
    if ($p) {
      $previousFile = $p->field_file_upload->entity->id();
    }
    else {
      $previousFile = NULL;
    }

    if (!$p || $currentFile != $previousFile) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Return existing dataset_versions for nid
   */
  private function getExistingDatasetVersions($nid) {
    $versions = [];
    $node = Node::load($nid);
    foreach ($node->field_dataset_version as $delta => $file_metadata_paragraph) {
      $p = $file_metadata_paragraph->entity;
      $versions[$delta]['target_id'] = $file_metadata_paragraph->target_id;
      $versions[$delta]['target_revision_id'] = $file_metadata_paragraph->target_revision_id;
    }

    return $versions;
  }

 }
