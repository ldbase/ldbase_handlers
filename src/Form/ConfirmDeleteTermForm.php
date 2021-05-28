<?php

namespace Drupal\ldbase_handlers\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines form to delete taxonomy term
 */
class ConfirmDeleteTermForm extends ConfirmFormBase {

  /**
   * Term to accept
   * @param Drupal\taxonomy\Entity\Term $taxonomy_term
   */
  protected $taxonomy_term;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Term $taxonomy_term = NULL) {
    $this->taxonomy_term = $taxonomy_term;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'ldbase_handlers_confirm_delete_term_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $route = 'view.taxonomy_terms_for_review.page_1';
    $url = Url::fromRoute($route, ['arg_0' => $this->taxonomy_term->id()]);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $term = $this->taxonomy_term->getName();
    return t('Delete %term', ['%term' => $term]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $message = 'If you confirm, the term will be deleted from LDbase.  This action cannot be undone.';
    return Markup::create($message);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term = $this->taxonomy_term;
    $term->delete();
    $message = 'Term deleted.';
    $this->messenger()->addStatus(t($message));
    $url = Url::fromRoute('ldbase_handlers.review_taxonomy_terms');
    $form_state->setRedirectUrl($url);
  }
}
