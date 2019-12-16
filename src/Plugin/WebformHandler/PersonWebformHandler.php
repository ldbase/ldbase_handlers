<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Create and edit Person nodes from a webform submission.
 *
 * @WebformHandler(
 *   id = "person_from_webform",
 *   label = @Translation("LDbase Person"),
 *   category = @Translation("Content"),
 *   description = @Translation("Creates and updates Person content nodes from Webform Submissions"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class PersonWebformHandler extends WebformHandlerBase {

  /**
    * {@inheritdoc}
    */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

    // Get the submitted form values
    $submission_array = $webform_submission->getData();

    $nid = $submission_array['node_id'];
    $title = $submission_array['preferred_name'];
    $body = [
      'value' => $submission_array['description'],
      'format' => 'basic_html',
    ];
    $field_website = $submission_array['website'];
    $field_location = $submission_array['location'];
    $field_first_name = $submission_array['first_name'];
    $field_middle_name = $submission_array['middle_name'];
    $field_last_name = $submission_array['last_name'];
    $field_email = $submission_array['email'];
    $field_orcid = $submission_array['orcid'];
    $field_web_presence = $submission_array['web_presence'];
    $field_professional_titles = $submission_array['professional_titles'];

    // $field_related_organizations
    $field_related_organizations = $submission_array['related_organizations'];

    // $field_areas_of_expertise
    $field_areas_of_expertise = $submission_array['areas_of_expertise'];

    // Get image upload, save to public files, attach to node.
    $image_fid = $submission_array['thumbnail'];
    if (!empty($image_fid)) {
      $file = \Drupal\file\Entity\File::load($image_fid);
      $path = $file->getFileUri();
      $data = file_get_contents($path);
      $node_img_file = file_save_data($data, 'public://' . $file->getFilename(), FILE_EXISTS_RENAME);
      $field_thumbnail = [
        'target_id' => $node_img_file->id(),
        'alt' => 'Thumbnail for ' . $title,
        'title' => $title,
      ];
    }
    else {
      $field_thumbnail = NULL;
    }

    if (!$nid) {
      // create node
      $node = Node::create([
        'type' => 'person',
        'status' => TRUE, // published
        'title' => $title,
        'body' => $body,
        'field_location' => $field_location,
        'field_website' => $field_website,
        'field_first_name' => $field_first_name,
        'field_middle_name' => $field_middle_name,
        'field_last_name' => $field_last_name,
        'field_email' => $field_email,
        'field_orcid' => $field_orcid,
        'field_web_presence' => $field_web_presence,
        'field_professional_titles' => $field_professional_titles,
        'field_related_organizations' => $field_related_organizations,
        'field_areas_of_expertise' => $field_areas_of_expertise,
        'field_thumbnail' => $field_thumbnail,
      ]);
    }
    else {
      // update node
      $node = Node::load($nid);
      $node->set('title', $title);
      $node->set('body', $body);
      $node->set('field_location', $field_location);
      $node->set('field_website', $field_website);
      $node->set('field_first_name',  $field_first_name);
      $node->set('field_middle_name', $field_middle_name);
      $node->set('field_last_name', $field_last_name);
      $node->set('field_email', $field_email);
      $node->set('field_orcid', $field_orcid);
      $node->set('field_web_presence', $field_web_presence);
      $node->set('field_professional_titles', $field_professional_titles);
      $node->set('field_related_organizations', $field_related_organizations);
      $node->set('field_areas_of_expertise', $field_areas_of_expertise);
      $node->set('field_thumbnail', $field_thumbnail);
    }

    //save the node
    $node->save();
  }
 }
