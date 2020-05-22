<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
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

    // date range select paragraph
    $field_activity_range_select = [];
    foreach ($submission_array['activity_range'] as $key => $value) {
      $field_from_month = $value['from_month'];
      $field_from_year = $value['from_year'];
      $field_to_month = $value['to_month'];
      $field_to_year = $value['to_year'];
      $activity_range_target_id = $value['activity_range_target_id'];
      $activity_range_target_revision_id = $value['activity_range_target_revision_id'];

      if (empty($activity_range_target_id)) {
        $date_range[$key] = Paragraph::create([
          'type' => 'date_range_selection',
          'field_from_month' => $field_from_month,
          'field_from_year' => $field_from_year,
          'field_to_month' => $field_to_month,
          'field_to_year' => $field_to_year,
        ]);
      }
      else {
        $date_range[$key] = Paragraph::load($activity_range_target_id);
        $date_range[$key]->set('field_from_month', $field_from_month);
        $date_range[$key]->set('field_from_year', $field_from_year);
        $date_range[$key]->set('field_to_month', $field_to_month);
        $date_range[$key]->set('field_to_year', $field_to_year);
      }

      $date_range[$key]->save();
      $field_activity_range_select[$key] = [
        'target_id' => $date_range[$key]->id(),
        'target_revision_id' => $date_range[$key]->getRevisionId(),
      ];
    }

    $field_website = $submission_array['website'];

    // grant information paragraph
    $field_grant_information = [];
    foreach ($submission_array['grant_information'] as $key => $value) {
      $field_funding_agency = $value['funding_agency'];
      $field_grant_number = $value['grant_number'];
      $grant_target_id = $value['grant_target_id'];
      $grant_target_revision_id = $value['grant_target_revision_id'];

      if (empty($grant_target_id)) {
        $grant_data[$key] = Paragraph::create([
          'type' => 'grant_information',
          'field_funding_agency' => $field_funding_agency,
          'field_grant_number' => $field_grant_number,
        ]);
      }
      else {
        $grant_data[$key] = Paragraph::load($grant_target_id);
        $grant_data[$key]->set('field_funding_agency', $field_funding_agency);
        $grant_data[$key]->set('field_grant_number', $field_grant_number);
      }

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
        'field_activity_range_select' => $field_activity_range_select,
        'field_website' => $field_website,
        'field_grant_information' => $field_grant_information,
        'field_project_type' => $field_project_type,
        'field_schooling' => $field_schooling,
        'field_curricula' => $field_curricula,
        'field_time_method' => $field_time_method,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully.');
      //save the node
      $node->save();
      // Create new project_group from Project
      $new_group_name = $title;
      $new_group = Group::create(['label' => $new_group_name, 'type' => 'project_group']);
      $new_group->save();
      // Add project to new group
      $plugin_id = 'group_node:' . $node->getType();
      $new_group->addContent($node, $plugin_id);
    }
    else {
      //update node
      $node = Node::load($nid);
      $node->set('title', $title);
      $node->set('body', $body);
      $node->set('field_related_persons', $field_related_persons);
      $node->set('field_related_organizations', $field_related_organizations);
      $node->set('field_doi', $field_doi);
      $node->set('field_activity_range_select', $field_activity_range_select);
      $node->set('field_website', $field_website);
      $node->set('field_grant_information', $field_grant_information);
      $node->set('field_project_type', $field_project_type);
      $node->set('field_schooling', $field_schooling);
      $node->set('field_curricula', $field_curricula);
      $node->set('field_time_method', $field_time_method);
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
    // project end date cannot come before start date
    $this->validateActivityRange($form_state);
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
    }
    $route_parameters = ['node' => $form_state->get('node_redirect')];
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));

    $form_state->setRedirect($route_name, $route_parameters);
  }

  /**
   * Validate Activity Range field
   * End date cannot come before start date
   */
  private function validateActivityRange(FormStateInterface $form_state) {
    $activity_ranges = $form_state->getValue('activity_range');
    if (empty($activity_ranges)) {
      return;
    }
    else {

      foreach ($activity_ranges as $delta => $row_array) {
        // if start month then start year
        if (!empty($row_array['from_month']) && empty($row_array['from_year'])) {
          $message = 'If you select a project start month, then you must enter a start year.';
          $form_state->setErrorByName('activity_range][items]['.$delta.'][from_year', $message);
        }
        // if end month then end year
        if (!empty($row_array['to_month']) && empty($row_array['to_year'])) {
          $message = 'If you select a project end month, then you must enter an end year.';
          $form_state->setErrorByName('activity_range][items]['.$delta.'][to_year', $message);
        }
        // if end year
          // then must have start year
          // and end year must be >= to start year
          // if years are equal and months are not empty, then to_month >= from_month
        if (!empty($row_array['to_year'])) {
          if (empty($row_array['from_year'])) {
            $message = 'If you have a project end year, then you must enter a start year.';
            $form_state->setErrorByName('activity_range][items]['.$delta, $message);
          }
          elseif ($row_array['to_year'] < $row_array['from_year']) {
            $message = 'The project end year must be equal to or greater the start year.';
            $form_state->setErrorByName('activity_range][items]['.$delta, $message);
          }
          elseif (($row_array['to_year'] == $row_array['from_year'])
          && (!empty($row_array['to_month']) && !empty($row_array['from_month']))) {
            if ($row_array['to_month'] < $row_array['from_month']) {
              $message = 'The project end month and year must be later or equal to the start month and year.';
              $form_state->setErrorByName('activity_range][items]['.$delta, $message);
            }
          }
        }
      }
    }
    //dd($form_state);
  }

 }
