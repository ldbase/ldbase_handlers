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
    $field_affiliated_documents = $submission_array['affiliated_documents'];
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
          'field_file_format' => $files_array[$key]['file_format'],
          'field_file_upload' => $paragraph_file_id,
          'field_format_version' => $files_array[$key]['format_version'],
          'field_file_version_description' => $files_array[$key]['file_version_description'],
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
    $field_affiliated_code = $submission_array['affiliated_code'];
    $field_unaffiliated_citation = $submission_array['unaffiliated_citation'];

    $field_affiliated_parents = $submission_array['affiliated_parents'];

    // hidden passed_id field
    $passed_id = $submission_array['passed_id'];
    if (!empty($passed_id)) {
      array_push($field_affiliated_parents, $passed_id);
    }

    // do nothing yet with unaffiliated parents

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'code',
        'status' => TRUE, // published
        'title' => $title,
        'field_affiliated_documents' => $field_affiliated_documents,
        'field_related_persons' => $field_related_persons,
        'field_code_type' => $field_code_type,
        'body' => $body,
        'field_doi' => $field_doi,
        'field_external_resource' => $field_external_resource,
        'field_file' => $field_file,
        'field_license' => $field_license,
        'field_publication_info' => $field_publication_info,
        'field_affiliated_code' => $field_affiliated_code,
        'field_unaffiliated_citation' => $field_unaffiliated_citation,
        'field_affiliated_parents' => $field_affiliated_parents,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully');
    }
    else {
      // update node
      $node = Node::load($nid);
      $node->set('title', $title);
      $node->set('field_affiliated_documents', $field_affiliated_documents);
      $node->set('field_related_persons', $field_related_persons);
      $node->set('field_code_type', $field_code_type);
      $node->set('body', $body);
      $node->set('field_doi', $field_doi);
      $node->set('field_external_resource', $field_external_resource);
      $node->set('field_file', $field_file);
      $node->set('field_license', $field_license);
      $node->set('field_publication_info', $field_publication_info);
      $node->set('field_affiliated_code', $field_affiliated_code);
      $node->set('field_unaffiliated_citation', $field_unaffiliated_citation);
      $node->set('field_affiliated_parents', $field_affiliated_parents);
      $form_state->set('redirect_message', $title . ' was updated successfully');
    }

    //save the node
    $node->save();
    // add node id to form_state to be used for redirection
    $code_id = $node->id();
    $form_state->set('node_redirect', $code_id);

    // add code to dataset - for now, until affiliated parents is completed
    if ($passed_id) {
      $associated_node = Node::load($passed_id);
      $affiliated_code = $associated_node->get('field_affiliated_code')->getValue();
      array_push($affiliated_code, $code_id);
      $associated_node->set('field_affiliated_code', $affiliated_code);
      $associated_node->save();
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
