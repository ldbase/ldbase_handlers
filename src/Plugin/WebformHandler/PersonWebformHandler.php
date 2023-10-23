<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
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
    $field_publishing_names = $submission_array['additional_publishing_names'];
    $field_email = $submission_array['email'];
    $field_do_not_contact = !($submission_array['person_contact_opt_in']);
    $field_orcid = $submission_array['orcid'];
    $field_google_scholar_id = $submission_array['google_scholar_id'];
    $field_web_presence = $submission_array['web_presence'];
    $field_professional_titles = $submission_array['professional_titles'];

    // $field_related_organizations
    $field_related_organizations = $submission_array['related_organizations'];

    // $field_areas_of_expertise
    $field_areas_of_expertise = $submission_array['areas_of_expertise'];

    // Get image upload, save to public files, attach to node.
    $image_fid = $submission_array['thumbnail'];
    if (!empty($image_fid)) {
      $new_fid = \Drupal::service('ldbase.webform_file_storage_service')->transferWebformFile($image_fid, 'person');
      $field_thumbnail = [
        'target_id' => $new_fid,
        'alt' => 'Thumbnail for ' . $title,
        'title' => $title,
      ];
    }
    else {
      $field_thumbnail = NULL;
    }

    $user = User::load(\Drupal::currentUser()->id());
    $user->setEmail(trim($field_email));
    $user->setUsername(trim($field_email));
    $user->set('mass_contact_opt_out', !($submission_array['mass_contact_opt_in']));
    $ldbase_password = $submission_array['ldbase_password'];
    if ($ldbase_password) {
      $user->setPassword(trim($ldbase_password));
    }
    $user->save();

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
        'field_publishing_names' => $field_publishing_names,
        'field_email' => $field_email,
        'field_do_not_contact' => $field_do_not_contact,
        'field_orcid' => $field_orcid,
        'field_google_scholar_id' => $field_google_scholar_id,
        'field_web_presence' => $field_web_presence,
        'field_professional_titles' => $field_professional_titles,
        'field_related_organizations' => $field_related_organizations,
        'field_areas_of_expertise' => $field_areas_of_expertise,
        'field_thumbnail' => $field_thumbnail,
      ]);
      $form_state->set('redirect_message', $title . ' was created successfully');
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
      $node->set('field_publishing_names', $field_publishing_names);
      $node->set('field_email', $field_email);
      $node->set('field_do_not_contact', $field_do_not_contact);
      $node->set('field_orcid', $field_orcid);
      $node->set('field_google_scholar_id', $field_google_scholar_id);
      $node->set('field_web_presence', $field_web_presence);
      $node->set('field_professional_titles', $field_professional_titles);
      $node->set('field_related_organizations', $field_related_organizations);
      $node->set('field_areas_of_expertise', $field_areas_of_expertise);
      $node->set('field_thumbnail', $field_thumbnail);
      $form_state->set('redirect_message', 'Your profile was updated successfully');
    }

    //save the node
    $node->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // check that user email is unique in the system
    $this->validateEmail($form_state, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // redirect to user profile
    $route_name = 'entity.user.canonical';
    $route_parameters = ['user' => \Drupal::currentUser()->id()];
    $this->messenger()->addStatus($this->t($form_state->get('redirect_message')));

    $form_state->setRedirect($route_name, $route_parameters);
  }

  /**
   * Check that user email is unique in system
   */
  private function validateEmail(FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $data = $webform_submission->getData();
    $email = $data['email'];

    $nid = $data['node_id'];
    $node = Node::load($nid);
    $original_email = !empty($node) ? $node->get('field_email')->value : NULL;

    if ($email <> $original_email) {
      $existing_ids = \Drupal::entityQuery('user')
        ->accessCheck(TRUE)
        ->condition('mail', $email)
        ->execute();

      if (!empty($existing_ids)) {
        $message = 'The email you entered is already associated with another LDbase user account.  Email addresses must be unique.';
        $form_state->setErrorByName('email', $message);
      }
    }

  }

 }
