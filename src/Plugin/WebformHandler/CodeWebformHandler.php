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
 * Create and edit Code nodes from a webform submission.
 *
 * @WebformHandler(
 *   id = "code_from_webform",
 *   label = @Translation("LDbase Code"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and updates Code content nodes from Webform Submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class CodeWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    // Get the submitted form values
    $submission_array = $webform_submission->getData();
    $nid = $submission_array['node_id'];
    $title = $submission_array['title'];
    $field_related_persons = $submission_array['authors'];
    if (!empty($submission_array['code_type'])) {
      $field_code_type = $submission_array['code_type'];
    }
    else {
      $field_code_type = [];
    }
    $body = [
      'value' => $submission_array['description'],
      'format' => 'basic_html',
    ];
    $field_doi = $submission_array['doi'];

    $field_code_upload_or_external = $submission_array['code_upload_or_external'];

    $field_external_resource = $submission_array['external_resource'];

    // code file
    $code_fid = $submission_array['code_file'];
    if (!empty($code_fid)) {
      $new_fid = \Drupal::service('ldbase.webform_file_storage_service')->transferWebformFile($code_fid, 'code');
      $field_code_file = $new_fid;
    }
    else {
      $field_code_file = NULL;
    }

    // file access restrictions paragraph
    $file_access_array = $submission_array['file_access_restrictions'];
    if (!empty($file_access_array)) {
      foreach ($file_access_array as $key => $value) {
        $field_file_embargoed = $value['file_embargoed'] == 'Yes' ? 1 : 0;
        $field_embaro_expiry_date = date($value['embargo_expiry_date']);
        $field_allow_file_requests = $value['allow_file_requests'] == 'Yes' ? 1 : 0;
        $access_restrictions_target_id = $value['access_restrictions_target_id'];
        $access_restrictions_target_revision_id = $value['access_restrictions_target_revision_id'];

        if (empty($access_restrictions_target_id)) {
          $access_data[$key] = Paragraph::create([
            'type' => 'file_access_restrictions',
            'field_file_embargoed' => $field_file_embargoed,
            'field_embaro_expiry_date' => $field_embaro_expiry_date,
            'field_allow_file_requests' => $field_allow_file_requests,
          ]);
        }
        else {
          $access_data[$key] = Paragraph::load($access_restrictions_target_id);
          $access_data[$key]->set('field_file_embargoed', $field_file_embargoed);
          $access_data[$key]->set('field_embaro_expiry_date', $field_embaro_expiry_date);
          $access_data[$key]->set('field_allow_file_requests', $field_allow_file_requests);
        }

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

    if (!empty($submission_array['license'])) {
      $field_license = $submission_array['license'];
    }
    else {
      $field_license = [];
    }

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

    // hidden passed_id field
    $passed_id = $submission_array['passed_id'];

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'code',
        'status' => TRUE, // published
        'title' => $title,
        'field_related_persons' => $field_related_persons,
        'field_code_type' => $field_code_type,
        'body' => $body,
        'field_doi' => $field_doi,
        'field_code_upload_or_external' => $field_code_upload_or_external,
        'field_external_resource' => $field_external_resource,
        'field_code_file' => $field_code_file,
        'field_file_access_restrictions' => $field_file_access_restrictions,
        'field_license' => $field_license,
        'field_publication_info' => $field_publication_info,
        'field_affiliated_parents' => $passed_id,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully');
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
      $node->set('field_related_persons', $field_related_persons);
      $node->set('field_code_type', $field_code_type);
      $node->set('body', $body);
      $node->set('field_doi', $field_doi);
      $node->set('field_code_upload_or_external', $field_code_upload_or_external);
      $node->set('field_external_resource', $field_external_resource);
      $node->set('field_code_file', $field_code_file);
      $node->set('field_file_access_restrictions', $field_file_access_restrictions);
      $node->set('field_license', $field_license);
      $node->set('field_publication_info', $field_publication_info);
      $form_state->set('redirect_message', $title . ' was updated successfully');
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
    // validate publication date
    $this->validatePublicationDate($form_state);
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
          $message = 'If you select a publication month, then you must select a publication year';
          $form_state->setErrorByName('publication_info][items]['.$delta.'][publication_year', $message);
        }
      }
    }
  }

 }
