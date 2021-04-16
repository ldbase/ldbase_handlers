<?php

namespace Drupal\ldbase_handlers\Form;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

/**
 * Defines form to confirm change (start or stop) of Dataset Subscription
 */
class ConfirmDatasetSubscriptionForm extends ConfirmFormBase {

  /**
   * UUID of dataset
   * @param Drupal\node\Entity\Node $node
   */
  protected $node;

  /**
   * ID of user
   * @param Drupal\user\Entity\User $user
   */
  protected $user;

  /**
   * change to be made
   * @param string $change
   */
  protected $change;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL, User $user = NULL, $change = NULL) {
    $this->node = $node;
    $this->user = $user;
    $this->change = $change;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'confirm_dataset_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $node_id = $this->node->id();
    $route = 'entity.node.canonical';
    $url_parameters = [
      'node' => $node_id,
    ];
    $url = Url::fromRoute($route, $url_parameters);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('%change receiving notifications?', ['%change' => ucfirst($this->change)]);
  }

  public function getDescription() {
    if ($this->change == 'start') {
      $message = '<p>' . t('If you confirm, you will receive an email when a new version of the dataset file is uploaded.') . '</p>';
    }
    else {
      $message = '<p>' . t('If you confirm, you will no longer be notified when a new version of the dataset file is uploaded.') . '</p>';
    }
    $description = "<div class='dataset-updates-confirmation'>" . $message . '</div>';
    return Markup::create($description);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // make sure the user id matches the logged in user
    if ($this->user->id() == \Drupal::currentUser()->id()) {
      $subscribers = $this->node->field_subscribed_users->getValue();
      // start messages
      if ($this->change == 'start') {
        $subscribers[] = ['target_id' => $this->user->id()];
        $message = 'You will be notified if the uploaded dataset file is updated.';
      }
      // stop messages
      else {
        foreach ($subscribers as $idx => $value) {
          if ($value['target_id'] == $this->user->id()) {
            unset($subscribers[$idx]);
          }
        }
        $message = 'You will no longer be notified if the uploaded dataset file is updated.';
      }
      $this->node->set('field_subscribed_users', $subscribers);
      $this->node->save();

    }
    else {
      $this->messenger()->addError($this->t('Your user id does not match the id passed into the form.'));
    }
    $this->messenger()->addStatus(t($message));
    $url = Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    $form_state->setRedirectUrl($url);
  }

}
