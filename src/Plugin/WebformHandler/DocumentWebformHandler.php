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
 * Create and edit Document nodes from a webform submission.
 *
 * @WebformHandler(
 *   id = "document_from_webform",
 *   label = @Translation("LDbase Document"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and updates Document content nodes from Webform Submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class DocumentWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Get the submitted form values
    $submission_array = $webform_submission->getData();
    $nid = $submission_array['node_id'];
    $published_flag = $submission_array['published_flag'];
    $title = $submission_array['title'];
    $field_related_persons = $submission_array['authors'];
    $body = [
      'value' => $submission_array['description'],
      'format' => 'basic_html',
    ];
    // codebook will not pass a document type which is a required field
    if (empty($submission_array['document_type'])) {
      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => 'Codebook', 'vid' => 'document_type']);
      $term = reset($term);
      $field_document_type = $term->id();
    }
    else {
      $field_document_type = $submission_array['document_type'];
    }

    $field_doi = $submission_array['doi'];
    $field_document_upload_or_external = $submission_array['document_uploaded_or_externally_linked'];
    $field_external_resource = $submission_array['external_resource'];

    if (!empty($submission_array['license'])) {
      $submitted_license = $submission_array['license'];
      $check_license = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'vid' => 'licenses',
            'field_valid_for' => 'document',
            'tid' => $submitted_license,
          ]);
      if (!empty($check_license)) {
        $field_license = $submitted_license;
        $field_license_other = NULL;
      }
      else {
        $field_license = NULL;
        $field_license_other = $submitted_license;
      }
    }
    else {
      $field_license = [];
      $field_license_other = NULL;
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

    // document file
    $document_fid = $submission_array['document_file'];
    if (!empty($document_fid)) {
      $new_fid = \Drupal::service('ldbase.webform_file_storage_service')->transferWebformFile($document_fid, 'document');
      $field_document_file = $new_fid;
    }
    else {
      $field_document_file = NULL;
    }

    $embargoed = $submission_array['embargoed']; // 1 if embargoed, 0 if unembargoed
    $embargo_expiry = $submission_array['embargo_expiry']; // date if set, empty if not
    $embargo_expiration_type = empty($embargo_expiry) ? 0 : 1;
    $embargo_exempt_users = $submission_array['embargo_exempt_users'];

    // hidden passed_id field
    $passed_id = $submission_array['passed_id'];

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'document',
        'status' => $published_flag,
        'title' => $title,
        'field_related_persons' => $field_related_persons,
        'body' => $body,
        'field_document_type' => $field_document_type,
        'field_doi' => $field_doi,
        'field_doc_upload_or_external' => $field_document_upload_or_external,
        'field_external_resource' => $field_external_resource,
        'field_license' => $field_license,
        'field_license_other' => $field_license_other,
        'field_publication_info' => $field_publication_info,
        'field_document_file' => $field_document_file,
        'field_affiliated_parents' => $passed_id,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully.');
      $form_state->set('confirm_doi', TRUE);
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
      $node->set('status', $published_flag);
      $node->set('title', $title);
      $node->set('field_related_persons', $field_related_persons);
      $node->set('body', $body);
      $node->set('field_document_type', $field_document_type);
      $node->set('field_doi', $field_doi);
      $node->set('field_doc_upload_or_external', $field_document_upload_or_external);
      $node->set('field_external_resource', $field_external_resource);
      $node->set('field_license', $field_license);
      $node->set('field_license_other', $field_license_other);
      $node->set('field_publication_info', $field_publication_info);
      $node->set('field_document_file', $field_document_file);
      $node->set('field_affiliated_parents', $passed_id);
      $form_state->set('redirect_message', $title . ' was updated successfully.');
      $form_state->set('confirm_doi', FALSE);
      //save the node
      $node->save();
    }

    // create or update embargo
    if ($embargoed) {
      // is this node embargoed? getAllEmbargoesByNids() ?
      $embargo_id = \Drupal::service('ldbase_embargoes.embargoes')->getAllEmbargoesByNids([$node->id()]);
      // load by embargo_id or create
      if (empty($embargo_id)) {
        $embargo = Node::create([
          'type' => 'embargo',
          'status' => TRUE, // published
          'title' => 'change_to_uuid',
          'field_embargo_type' => 0,
          'field_embargoed_node' => $node->id(),
          'field_expiration_type' => $embargo_expiration_type,
          'field_expiration_date' => $embargo_expiry,
          'field_exempt_users' => $embargo_exempt_users,
        ]);
        $embargo->set('title', $embargo->uuid->value);
        $embargo->save();
        \Drupal::messenger()->addMessage("Your embargo has been created.");
      }
      else {
        $embargo = Node::load($embargo_id[0]);
        $embargo->set('field_expiration_type', $embargo_expiration_type);
        $embargo->set('field_expiration_date', $embargo_expiry);
        $embargo->set('field_exempt_users', $embargo_exempt_users);
        $embargo->save();
        \Drupal::messenger()->addMessage("Your embargo has been updated.");
      }
    }
    else {
      // if no restriction, check for embargoes and delete
      $embargo_id = \Drupal::service('ldbase_embargoes.embargoes')->getAllEmbargoesByNids([$node->id()]);
      if (!empty($embargo_id)) {
        $embargo_to_delete = \Drupal::entityTypeManager()->getStorage('node')->load($embargo_id[0]);
        $embargo_to_delete->delete();
        \Drupal::messenger()->addMessage("Your embargo has been deleted.");
      }
    }

    // put new nid in form_state may be used for redirection
    $form_state->set('this_nid', $node->id());
    // add node id to form_state to be used for redirection
    $form_state->set('node_redirect', $node->uuid());
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
    $confirm_doi = $form_state->get('confirm_doi');
    // if no DOI redirect to DOI creation confirmation
    if ($confirm_doi && empty($submission_array['doi'])) {
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
