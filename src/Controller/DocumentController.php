<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
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
    // get manage members text or link (if access)
    $exempt_users_description = $this->getEmbargoExemptUsersDescription($node);
    // overwrite exempt users description
    $webform['elements']['embargo_exempt_users']['#description']['#markup'] = $exempt_users_description;

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
    $published_flag = $node->status->value;
    $title = $node->getTitle();
    $description = $node->get('body')->value;
    $authors = $node->get('field_related_persons')->getValue();
    $document_type = $node->get('field_document_type')->target_id;
    $doi = $node->get('field_doi')->getValue();
    $document_upload_or_external = $node->get('field_doc_upload_or_external')->value;
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

    $document_file = $node->field_document_file->entity;
    $document_file_id = !empty($document_file) ? $document_file->id() : NULL;
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
        'document_type' => $document_type,
        'doi' => $doi,
        'document_uploaded_or_externally_linked' => $document_upload_or_external,
        'codebook_uploaded_or_externally_linked' => $document_upload_or_external,
        'external_resource' => $external_resource,
        'license' => $license,
        'publication_info' => $publication_info,
        'document_file' => $document_file_id,
        'passed_id' => $passed_id,
        'embargoed' => $embargoed,
        'embargo_expiry' => $embargo_expiry,
        'embargo_exempt_users' => $embargo_exempt_users,
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
    // get manage members text or link (if access)
    $exempt_users_description = $this->getEmbargoExemptUsersDescription($node);
    // overwrite exempt users description
    $webform['elements']['embargo_exempt_users']['#description']['#markup'] = $exempt_users_description;
    return $webform;
  }

  /**
   * create description text with link for embargo_exemt_users field
   */
  private function getEmbargoExemptUsersDescription(NodeInterface $node) {
    $nid = $node->id();
    // get top project uuid
    $project = \Drupal::service('ldbase.object_service')->getLdbaseRootProjectNodeFromLdbaseObjectNid($nid);
    // get group id
    $group_contents = GroupContent::loadByEntity($project);
    $group = array_pop($group_contents)->getGroup();

    // create link
    $route_name = 'view.group_members.ldbase_project';
    $link_text = 'Project Administrators and Project Editors';
    $link_url = Url::fromRoute($route_name, ['node' => $project->uuid(), 'group' => $group->id()], ['attributes' => ['target' => '_blank']]);
    $rendered_link = Link::fromTextAndUrl($link_text, $link_url);
    // if user has access return link, otherwise return text
    $manage_members_link = $link_url->access($this->currentUser()) ? $rendered_link->toString() : $link_text;

    // create exempt users field description
    $exempt_users_description = "When you restrict public access to your data, {$manage_members_link} are the only people who can view those files. You can, however, allow certain individuals, such as a collaborator or a data requester whom you approve of, to override the restriction to view/download the data. Simply enter their LDbase name below. They will then gain access to this data, but they will not be able to to perform any actions that your Project Administrators and Project Editors can do.";

    return $exempt_users_description;
  }

}
