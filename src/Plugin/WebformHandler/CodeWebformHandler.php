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
    $published_flag = $submission_array['published_flag'];
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

    if (!empty($submission_array['license'])) {
      $submitted_license = $submission_array['license'];
      $check_license = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'vid' => 'licenses',
            'field_valid_for' => 'code',
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

    // code file
    $code_fid = $submission_array['code_file'];
    if (!empty($code_fid)) {
      $new_fid = \Drupal::service('ldbase.webform_file_storage_service')->transferWebformFile($code_fid, 'code');
      $field_code_file = $new_fid;
    }
    else {
      $field_code_file = NULL;
    }

    $embargoed = $submission_array['embargoed']; // 1 if embargoed, 0 if unembargoed
    $embargo_expiry = $submission_array['embargo_expiry']; // date if set, empty if not
    $embargo_expiration_type = empty($embargo_expiry) ? 0 : 1;
    $embargo_exempt_users = $submission_array['embargo_exempt_users'];

    // hidden passed_id field
    $passed_id = $submission_array['passed_id'];
    $parent_node = Node::load($passed_id);

    // if unpublished add '(unpublished)' to title if not there already
    $unpublished_pattern = '/\(unpublished\)$/';
    if (!$published_flag) {
      if (preg_match($unpublished_pattern, trim($title)) === 0) {
        $title .= ' (unpublished)';
      }
    }
    else {
      $published_title = preg_replace($unpublished_pattern, '', trim($title));
      $title = trim($published_title);
    }

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'code',
        'status' => $published_flag,
        'title' => $title,
        'field_related_persons' => $field_related_persons,
        'field_code_type' => $field_code_type,
        'body' => $body,
        'field_doi' => $field_doi,
        'field_code_upload_or_external' => $field_code_upload_or_external,
        'field_external_resource' => $field_external_resource,
        'field_license' => $field_license,
        'field_license_other' => $field_license_other,
        'field_code_file' => $field_code_file,
        'field_affiliated_parents' => $passed_id,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully');
      //save the node
      $node->save();
      // get groupId of parent that was passed in - assumes Group Cardinality = 1

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
      $existing_flag = $parent_node->get('status')->value;
      $status_has_changed = $published_flag != $existing_flag ? true : false;
      $parent_published_flag = $parent_node->get('status')->value;
      // if parent node is unpublished, make sure that this node is also unpublished
      if ($published_flag && !$parent_published_flag) {
        $published_flag = false;
        $this->messenger()->addStatus($this->t('This code was unpublished, because it was nested under an unpublished item.'));
      }

      $node->set('status', $published_flag);
      $node->set('title', $title);
      $node->set('field_related_persons', $field_related_persons);
      $node->set('field_code_type', $field_code_type);
      $node->set('body', $body);
      $node->set('field_doi', $field_doi);
      $node->set('field_code_upload_or_external', $field_code_upload_or_external);
      $node->set('field_external_resource', $field_external_resource);
      $node->set('field_license', $field_license);
      $node->set('field_license_other', $field_license_other);
      $node->set('field_code_file', $field_code_file);
      $node->set('field_affiliated_parents', $passed_id);
      $form_state->set('redirect_message', $title . ' was updated successfully');
      //save the node
      $node->save();

      // if unpublished then unpublish children
      if (!$published_flag) {
        $unpublished_children = \Drupal::service('ldbase_handlers.publish_status_service')->unpublishChildNodes($nid);
        if ($unpublished_children) {
          $text = count($unpublished_children) > 1 ? 'nodes' : 'node';
          $this->messenger()
            ->addStatus($this->t('%count child %text also unpublished.', ['%count' => count($unpublished_children), '%text' => $text]));
        }
      }
      else {
        $has_unpublished_child = \Drupal::service('ldbase_handlers.publish_status_service')->hasUnpublishedChild($nid);
        if ($status_has_changed && $has_unpublished_child) {
          $this->messenger()->addStatus($this->t('Remember to publish the other items in your project hierarchy so the metadata will be shared.'));
        }
      }
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
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $submission_array = $webform_submission->getData();
    // redirect to node view
    $route_name = 'entity.node.canonical';
    $node_id = $form_state->get('this_nid');
    $form_state->set('node_redirect', $node_id);
    $route_parameters = ['node' => $form_state->get('node_redirect')];
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));

    $form_state->setRedirect($route_name, $route_parameters);
  }

 }
