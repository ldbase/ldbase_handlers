<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CodeController extends ControllerBase {

  /**
   * CodeController constructor
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * Gets title for "Add Code to ..." page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getAddCodeTitle(NodeInterface $node) {
    return 'Add Code to ' . ucfirst($node->getType()) . ': ' . $node->getTitle();
  }

  /**
   * Gets title for edit page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getEditTitle(NodeInterface $node) {
    return 'Edit Code: ' . $node->getTitle();
  }

  /**
   * Loads code node data into a webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editCode(NodeInterface $node) {
    // get node data
    $nid = $node->id();
    $published_flag = $node->status->value;
    $title = $node->getTitle();
    $description = $node->get('body')->value;
    $authors = $node->get('field_related_persons')->getValue();
    $code_type = [];
    foreach ($node->get('field_code_type')->getValue() as $delta => $value) {
      $code_type[$delta] = $value['target_id'];
    }
    $doi = $node->get('field_doi')->getValue();
    $code_upload_or_external = $node->get('field_code_upload_or_external')->value;
    $external_resource = $node->get('field_external_resource')->uri;
    $license = $node->get('field_license')->target_id;
    if (empty($license)) {
      $license = $node->get('field_license_other')->value;
    }

    // publication info paragraph
    $publication_info = [];
    foreach ($node->field_publication_info as $delta => $pub_paragraph) {
      $p = $pub_paragraph->entity;
      $publication_info[$delta]['publication_month'] = $p->field_publication_month->value;
      $publication_info[$delta]['publication_year'] = $p->field_publication_year->value;
      $publication_info[$delta]['publication_source'] = $p->get('field_publication_source')->uri;
      $publication_info[$delta]['publication_target_id'] = $pub_paragraph->target_id;
      $publication_info[$delta]['publication_target_revision_id'] = $pub_paragraph->target_revision_id;
    }

    $code_file = $node->field_code_file->entity;
    $code_file_id = !empty($code_file) ? $code_file->id() : NULL;

    $passed_id = $node->get('field_affiliated_parents')->target_id;

    //Set $embargoed
    //Set $embargo_expiry
    $embargo_id = \Drupal::service('ldbase_embargoes.embargoes')->getAllEmbargoesByNids(array($node->id()));
    $embargo = !empty($embargo_id) ? \Drupal::entityTypeManager()->getStorage('node')->load($embargo_id[0]) : '';
    $embargoed = !empty($embargo);
    $embargo_expiry = empty($embargo) ? '' : $embargo->get('field_expiration_date')->value;
    $embargo_exempt_users = empty($embargo) ? [] : $embargo->get('field_exempt_users')->getValue();

    $values = [
      'data' => [
        'node_id' => $nid,
        'published_flag' => $published_flag,
        'title' => $title,
        'description' => $description,
        'authors' => $authors,
        'code_type' => $code_type,
        'doi' => $doi,
        'code_upload_or_external' => $code_upload_or_external,
        'external_resource' => $external_resource,
        'license' => $license,
        'publication_info' => $publication_info,
        'code_file' => $code_file_id,
        'passed_id' => $passed_id,
        'embargoed' => $embargoed,
        'embargo_expiry' => $embargo_expiry,
        'embargo_exempt_users' => $embargo_exempt_users,
      ]
    ];

    $operation = 'edit';

    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_code');
    $webform = $webform->getSubmissionForm($values,$operation);
    return $webform;
  }

  /**
   * Loads Code Webform and associates dataset id
   *
   * The dataset passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function addCode(NodeInterface $node) {
    $node_type = $node->getType();
    $passed_id = $node->id();

    $values = [
      'data' => [
        'passed_id' => $passed_id,
      ]
    ];

    $operation = 'add';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_code');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

}
