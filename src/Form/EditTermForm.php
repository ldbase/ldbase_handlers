<?php

namespace Drupal\ldbase_handlers\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to edit taxonomy terms during review
 */
class EditTermForm extends FormBase {

  /**
   * Entity Type Manager
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldbase_handlers_edit_term_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Term $taxonomy_term = NULL) {
    $tid = $taxonomy_term->id();
    $default_name = $taxonomy_term->getName();
    $needs_review = $taxonomy_term->field_needs_review->value;

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Use this form to edit the term %name.', ['%name' => $default_name]),
    ];

    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Taxonomy Term'),
      '#required' => true,
      '#default_value' => $default_name,
    ];

    $form['field_needs_review'] = [
      '#type' => 'radios',
      '#title' => t('Keep term in review?'),
      '#default_value' => $needs_review,
      '#options' => [
        '1' => t('Yes'),
        '0' => t('No'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    // don't do client validation, use inline instead
    $form['#attributes']['novalidate'] = true;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tid = $form_state->getValue('tid');
    $name = $form_state->getValue('name');
    $field_needs_review = $form_state->getValue('field_needs_review');
    // set redirect based on whether term remains in review
    if ($field_needs_review) {
      $route = 'view.taxonomy_terms_for_review.page_1';
      $url = Url::fromRoute($route, ['arg_0' => $tid]);
    }
    else {
      $url = Url::fromRoute('ldbase_handlers.review_taxonomy_terms');
    }

    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    $term->set('name', $name);
    $term->set('field_needs_review', $field_needs_review);
    $term->save();

    $message = 'Term updated.';
    $this->messenger()->addStatus(t($message));
    $form_state->setRedirectUrl($url);
  }

}
