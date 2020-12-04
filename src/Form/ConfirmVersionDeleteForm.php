<?php

namespace Drupal\ldbase_handlers\Form;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form to confirm deletion of a dataset version paragraph/
 */
class ConfirmVersionDeleteForm extends ConfirmFormBase {

  /**
   * UUID of parent node
   * @param Drupal\node\Entity\Node $node
   */
  protected $node;

  /**
   * Paragraph Id of version
   * @param Drupal\paragraphs\Entity\Paragraph $paragraph
   */
  protected $paragraph;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL, Paragraph $paragraph = NULL) {
    $this->node = $node;
    $this->paragraph = $paragraph;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      $pid = $this->paragraph->id();
      $dataset_versions = $this->node->field_dataset_version;
      foreach ($dataset_versions as $key => $version) {
        if ($version->entity->id() == $pid) {
          $this->node->field_dataset_version->removeItem($key);
          $this->node->save();
        }
      }
      $this->paragraph->delete();

      $this->messenger()->addStatus($this->t('The dataset version has been successfully deleted.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_version_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $route = 'view.dataset_versions.all_versions';
    $url_parameters = [
      'uuid' => $this->node->uuid(),
      'node' => $this->node->id(),
    ];
    $url = Url::fromRoute($route, $url_parameters);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $version_label = $this->paragraph->get('field_file_version_label')->value;
    return t('Do you want to delete version %label?',['%label' => $version_label]);
  }

}
