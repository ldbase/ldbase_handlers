<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LdbaseController extends ControllerBase {

  /**
   * LdbaseController constructor
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
   * Loads organzation node data into a webform for editing.
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editOrganization(NodeInterface $node) {

    // get node data
    $nid = $node->id();
    $name = $node->getTitle();
    $description = $node->get('body')->value;
    $website = $node->get('field_website')->getValue();
    $location = $node->get('field_location')->getValue();
    $image = $node->field_thumbnail->entity;
    $image_fid = !empty($image) ? $image->id() : NULL;

     $values = [
      'data' => [
        'name' => $name,
        'description' => $description,
        'website' => $website,
        'location' => $location,
        'node_id' => $nid,
        'image' => $image_fid,
      ]
    ];

    $operation = 'edit';
    // get organization webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_organization');
    $webform = $webform->getSubmissionForm($values, $operation);

    return $webform;
  }

  /**
   * Loads person node data into a webform for editing.
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editPerson(NodeInterface $node) {

    // get node data
    $nid = $node->id();
    $preferred_name = $node->getTitle();
    $description = $node->get('body')->value;
    $location = $node->get('field_location')->getValue();
    $website = $node->get('field_website')->getValue();
    $first_name = $node->get('field_first_name')->value;
    $middle_name = $node->get('field_middle_name')->value;
    $last_name = $node->get('field_last_name')->value;
    $email = $node->get('field_email')->getValue();
    $orcid = $node->get('field_orcid')->value;
    $web_presence = $node->get('field_web_presence')->getValue();
    $professional_titles = $node->get('field_professional_titles')->getValue();
    foreach ($node->get('field_related_organizations')->getValue() as $delta => $value) {
      $related_organizations[$delta] = $value['target_id'];
    }
    $areas_of_expertise = $node->get('field_areas_of_expertise')->getValue();

    $thumbnail = $node->field_thumbnail->entity;
    $thumbnail_fid = !empty($thumbnail) ? $thumbnail->id() : NULL;

    $values = [
      'data' => [
        'node_id' => $nid,
        'preferred_name' => $preferred_name,
        'description' => $description,
        'location' => $location,
        'website' => $website,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'email' => $email,
        'orcid' => $orcid,
        'web_presence' => $web_presence,
        'professional_titles' => $professional_titles,
        'related_organizations' => $related_organizations,
        'areas_of_expertise' => $areas_of_expertise,
        'thumbnail' => $thumbnail_fid,
      ]
    ];

    $operation = 'edit';
    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_person');
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
    $external_resource = $node->get('field_external_resource')->uri;
    // file paragraph
    foreach ($node->field_file as $delta => $file_paragraph) {
      $p = $file_paragraph->entity;
      $file[$delta]['file_format'] = $p->field_file_format->target_id;
      $file[$delta]['file_upload'] = $p->field_file_upload->entity->id();
      $file[$delta]['file_version_description'] = $p->field_file_version_description->value;
      $file[$delta]['format_version'] = $p->field_format_version->value;
    }
    $license = $node->get('field_license')->target_id;
    // publication info paragraph
    foreach ($node->field_publication_info as $delta => $pub_paragraph) {
      $p = $pub_paragraph->entity;
      $publication_info[$delta]['publication_date'] = $p->field_publication_date->value;
      $publication_info[$delta]['publication_source'] = $p->get('field_publication_source')->uri;
    }
    $unaffiliated_citation = $node->get('field_unaffiliated_citation')->getValue();

    $values = [
      'data' => [
        'node_id' => $nid,
        'title' => $title,
        'description' => $description,
        'authors' => $authors,
        'document_type' => $document_type,
        'doi' => $doi,
        'external_resource' => $external_resource,
        'file' => $file,
        'license' => $license,
        'publication_info' => $publication_info,
        'unaffiliated_citation' => $unaffiliated_citation,
      ]
    ];

    $operation = 'edit';
    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_document');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
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
    $affiliated_documents = $node->get('field_affiliated_documents')->getValue();
    $authors = $node->get('field_related_persons')->getValue();
    $code_type = $node->get('field_code_type')->target_id;
    $doi = $node->get('field_doi')->getValue();
    $external_resource = $node->get('field_external_resource')->uri;
    // file paragraph
    foreach ($node->field_file as $delta => $file_paragraph) {
      $p = $file_paragraph->entity;
      $file[$delta]['file_format'] = $p->field_file_format->target_id;
      $file[$delta]['file_upload'] = $p->field_file_upload->entity->id();
      $file[$delta]['file_version_description'] = $p->field_file_version_description->value;
      $file[$delta]['format_version'] = $p->field_format_version->value;
    }
    $license = $node->get('field_license')->target_id;
    // publication info paragraph
    foreach ($node->field_publication_info as $delta => $pub_paragraph) {
      $p = $pub_paragraph->entity;
      $publication_info[$delta]['publication_date'] = $p->field_publication_date->value;
      $publication_info[$delta]['publication_source'] = $p->get('field_publication_source')->uri;
    }
    $unaffiliated_citation = $node->get('field_unaffiliated_citation')->getValue();

    $values = [
      'data' => [
        'node_id' => $nid,
        'title' => $title,
        'description' => $description,
        'affiliated_documents' => $affiliated_documents,
        'authors' => $authors,
        'code_type' => $code_type,
        'doi' => $doi,
        'external_resource' => $external_resource,
        'file' => $file,
        'license' => $license,
        'publication_info' => $publication_info,
        'unaffiliated_citation' => $unaffiliated_citation,
      ]
    ];

    $operation = 'edit';
    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_code');
    $webform = $webform->getSubmissionForm($values,$operation);
    return $webform;

  }

}
