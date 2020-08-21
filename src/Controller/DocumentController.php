<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DocumentController extends ControllerBase {

  /**
   * DocumentController constructor
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
   * Gets title for "Add Document to ..." page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getAddDocumentTitle(NodeInterface $node, $document_type = NULL) {
    $doc_type = empty($document_type) ? 'Document' : 'Codebook';
    return 'Add ' . $doc_type . ' to ' . ucfirst($node->getType()) . ': ' . $node->getTitle();
  }

  /**
   * Gets title for edit page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getEditTitle(NodeInterface $node) {
    $uuid = $node->uuid();
    $doc_type = \Drupal::service('ldbase.object_service')->isLdbaseCodebook($uuid) ? 'Codebook' : 'Document';
    return "Edit {$doc_type}: " . $node->getTitle();
  }

  /**
   * Loads Document Webform and associates project id
   *
   * The Project passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function addDocument(NodeInterface $node, $document_type = NULL) {
    $node_type = $node->getType();
    $passed_id = $node->id();

    if ($document_type) {
      $tid = '';
      $vocabulary = 'document_type';

      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $document_type, 'vid' => $vocabulary]);
      $term = reset($term);
      $tid = $term->id();
      $doc_type = $tid;
    }
    else {
      $doc_type = NULL;
    }

    $values = [
      'data' => [
        'passed_id' => $passed_id,
        'document_type' => $doc_type,
      ]
    ];
    if ($document_type === 'Codebook') {
      $use_webform = 'create_update_codebook';
    }
    else {
      $use_webform = 'create_update_document';
    }
    $operation = 'add';
    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($use_webform);
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

  /**
   * Loads Document node data into webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editDocument(NodeInterface $node) {

    // get node data
    $nid = $node->id();
    $title = $node->getTitle();
    $description = $node->get('body')->value;
    $authors = $node->get('field_related_persons')->getValue();
    $document_type = $node->get('field_document_type')->target_id;
    $doi = $node->get('field_doi')->getValue();
    $document_upload_or_external = $node->get('field_doc_upload_or_external')->value;
    $external_resource = $node->get('field_external_resource')->uri;
    $license = $node->get('field_license')->target_id;

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

    $document_file = $node->field_document_file->entity;
    $document_file_id = !empty($document_file) ? $document_file->id() : NULL;

    //Set $embargoed
    //Set $embargo_expiry

    $values = [
      'data' => [
        'node_id' => $nid,
        'title' => $title,
        'description' => $description,
        'authors' => $authors,
        'document_type' => $document_type,
        'doi' => $doi,
        'document_uploaded_or_externally_linked' => $document_upload_or_external,
        'codebook_uploaded_or_externally_linked' => $document_upload_or_external,
        'external_resource' => $external_resource,
        'license' => $license,
        'publication_info' => $publication_info,
        'document_file' => $document_file_id,
        //'embargoed' => $embargoed,
        //'embargo_expiry' => $embargo_expiry,
      ]
    ];
    $document_type_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($document_type)->getName();
    if ($document_type_term === 'Codebook') {
      $use_webform = 'create_update_codebook';
    }
    else {
      $use_webform = 'create_update_document';
    }
    $operation = 'edit';
    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($use_webform);
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

}
