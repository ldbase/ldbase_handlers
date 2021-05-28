<?php

namespace Drupal\ldbase_handlers\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to replace a taxonomy term with a different selected term
 */
class ChangeTermForm extends FormBase {

  /**
   * Entity Type Manager
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Entity Field Manager
   * @var \Drupal\Core\Entity\EntityFieldManager;
   */
  private $fieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManager $fieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldManager = $fieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldbase_handlers_change_term_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Term $taxonomy_term = NULL) {
    $tid = $taxonomy_term->tid->value;
    $vid = $taxonomy_term->vid->target_id;
    $original_term = $taxonomy_term->name->value;

    $options = $this->getVocabularyOptions($tid, $vid);

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Select a term to replace %name.', ['%name' => $original_term]),
    ];

    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $tid,
    ];

    $form['vid'] = [
      '#type' => 'hidden',
      '#value' => $vid,
    ];

    $form['use_instead'] = [
      '#type' => 'select',
      '#title' => t('Use this term instead'),
      '#options' => $options,
      '#required' => true,
    ];

    $form['message_to_users'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to Users'),
      '#required' => true,
      '#description' => $this->t("This text will be sent to users that had used this term. Use it to explain why you changed the term."),
      '#description_display' => 'before',
      '#cols' => 60,
      '#rows' => 5,
      '#resizable' => 'vertical',
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
    // get submitted values
    $tid = $form_state->getValue('tid');
    $vid = $form_state->getValue('vid');
    $use_instead = $form_state->getValue('use_instead');
    $message_to_users = $form_state->getValue('message_to_users');

    // find the fields that use this vocabulary
    $vocabulary_fields = $this->getVocabularyFields($vid);
    // get the nodes with the field that reference the old term
    $nids = [];
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    foreach ($vocabulary_fields as $bundle => $fields) {
      foreach ($fields as $field) {
        $result = $query->condition('type', $bundle)
                    ->condition($field, $tid)
                    ->accessCheck(false)
                    ->execute();
        $nids = array_merge($nids, $result);
      }
    }
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $message_nodes = [];
    // add new term reference
    foreach ($nodes as $node) {
      foreach ($vocabulary_fields as $bundle => $fields) {
        if ($bundle == $node->getType()) {
          foreach ($fields as $field) {
            $field_values = $node->get($field)->getValue();
            // add use instead
            array_push($field_values, ['target_id' => $use_instead]);
            $node->set($field, $field_values);
          }
        }
      }
      $node->save();
      // gather info for message
      array_push($message_nodes, ['nid' => $node->id(),]);
    }
    // remove old term
    $old_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
    $old_term_name = $old_term->name->value;
    $old_term->delete();

    $new_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($use_instead);
    $new_term_name = $new_term->name->value;
    // send message to node authors that the term has changed
    if (!empty($message_nodes)) {
      $ldbase_message_service = \Drupal::service('ldbase_handlers.message_service');
      $message_array = [
        'old_term_name' => $old_term_name,
        'new_term_name' => $new_term_name,
        'reason_for_change' => $message_to_users,
        'message_nodes' => $message_nodes,
      ];

      $ldbase_message_service->taxonomyTermChanged($message_array);
    }

    $url = Url::fromRoute('ldbase_handlers.review_taxonomy_terms');
    $this->messenger()->addStatus(t('The term %old_term has been replaced with %new_term',['%old_term' => $old_term_name,'%new_term' => $new_term_name]));
    $form_state->setRedirectUrl($url);
  }

  /**
   * Helper functions
   */
  private function getVocabularyOptions($tid, $vid) {
    $options = [];
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);

    foreach ($terms as $term) {
      // don't include the reviewed term
      if ($term->tid != $tid) {
        $options[$term->tid] = $term->name;
      }
    }
    return $options;
  }

  private function getVocabularyFields($vid) {
    $vocabulary_fields = [];
    // bundles to check
    $bundles = ['project','dataset'];
    foreach ($bundles as $bundle) {
      // get the fields for the bundles
      $fields = $this->fieldManager->getFieldDefinitions('node', $bundle);
      // check the handler settings for each field
      foreach ($fields as $field) {
        $handler = $field->getSettings()['handler'];
        if ($handler == 'default:taxonomy_term') {
          $field_config = $field->get('dependencies')['config'];
          foreach ($field_config as $config) {
            if ($config == 'taxonomy.vocabulary.' . $vid) {
              $vocabulary_fields[$bundle][] = $field->getName();
            }
          }
        }
      }
    }
    return $vocabulary_fields;
  }

}
