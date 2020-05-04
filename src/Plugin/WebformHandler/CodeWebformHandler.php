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

    // document file
    $document_fid = $submission_array['document_file'];
    if (!empty($document_fid)) {
      $file = \Drupal\file\Entity\File::load($document_fid);
      $path = $file->getFileUri();
      $data = file_get_contents($path);
      $node_document_file = file_save_data($data, 'public://' . $file->getFilename(), FILE_EXISTS_RENAME);
      $field_document_file = $node_document_file->id();
    }
    else {
      $field_document_file = NULL;
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
        $paragraph_data[$key] = Paragraph::create([
          'type' => 'publication_metadata',
          'field_publication_date' => $publications_array[$key]['publication_date'],
          'field_publication_source' => $publications_array[$key]['publication_source'],
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
        'type' => 'code',
        'status' => TRUE, // published
        'title' => $title,
        'field_related_persons' => $field_related_persons,
        'field_code_type' => $field_code_type,
        'body' => $body,
        'field_doi' => $field_doi,
        'field_code_upload_or_external' => $field_code_upload_or_external,
        'field_external_resource' => $field_external_resource,
        'field_document_file' => $field_document_file,
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
      $node->set('field_document_file', $field_document_file);
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

 }
