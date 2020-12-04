<?php

namespace Drupal\ldbase_handlers\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\webform\WebformInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Create and edit Person nodes from a webform submission.
 *
 * @WebformHandler(
 *   id = "update_password_from_webform",
 *   label = @Translation("LDbase Password Update"),
 *   category = @Translation("LDbase User"),
 *   description = @Translation("Updates user account password for LDbase Person"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */

 class PasswordChangeWebformHandler extends WebformHandlerBase {

   /**
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
      // Get the submitted form values
      $submission_array = $webform_submission->getData();
      $ldbase_password = $submission_array['ldbase_password'];
      $user = User::load(\Drupal::currentUser()->id());
      $user->setPassword(trim($ldbase_password));
      $user->save();
    }

    /**
   * {@inheritdoc}
   */
    public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
      // redirect to user profile
      $route_name = 'entity.user.canonical';
      $route_parameters = ['user' => \Drupal::currentUser()->id()];
      $this->messenger()->addStatus($this->t('Your password has been changed.'));

      $form_state->setRedirect($route_name, $route_parameters);
    }

 }
