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
   * Loads code node data into a webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editCode(NodeInterface $node) {

    // get node data
    $nid = $node->id();
    $title = $node->getTitle();
    $description = $node->get('body')->value;
    $authors = $node->get('field_related_persons')->getValue();
    $code_type = $node->get('field_code_type')->target_id;
    $doi = $node->get('field_doi')->getValue();
    $external_resource = $node->get('field_external_resource')->uri;
    // file paragraph
    $file = [];
    foreach ($node->field_file as $delta => $file_paragraph) {
      $p = $file_paragraph->entity;
      $file[$delta]['file_format'] = $p->field_file_format->target_id;
      $file[$delta]['file_upload'] = $p->field_file_upload->entity->id();
      $file[$delta]['file_version_description'] = $p->field_file_version_description->value;
    }
    $license = $node->get('field_license')->target_id;
    // publication info paragraph
    $publication_info = [];
    foreach ($node->field_publication_info as $delta => $pub_paragraph) {
      $p = $pub_paragraph->entity;
      $publication_info[$delta]['publication_date'] = $p->field_publication_date->value;
      $publication_info[$delta]['publication_source'] = $p->get('field_publication_source')->uri;
    }

    $values = [
      'data' => [
        'node_id' => $nid,
        'title' => $title,
        'description' => $description,
        'authors' => $authors,
        'code_type' => $code_type,
        'doi' => $doi,
        'external_resource' => $external_resource,
        'file' => $file,
        'license' => $license,
        'publication_info' => $publication_info,
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
