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
    $websiteArray = $node->get('field_website')->getValue();
    foreach ($websiteArray as $key => $value) {
      $website[$key] = $value['uri'];
    }
    $locationArray = $node->get('field_location')->getValue();
    foreach ($locationArray as $key=>$value) {
      $location[$key] = $value;
    }
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
    $locationArray = $node->get('field_location')->getValue();
    foreach ($locationArray as $key=>$value) {
      $location[$key] = $value;
    }
    $websiteArray = $node->get('field_website')->getValue();
    foreach ($websiteArray as $key => $value) {
      $website[$key] = $value['uri'];
    }
    $first_name = $node->get('field_first_name')->getValue()[0];
    $middle_name = $node->get('field_middle_name')->getValue()[0];
    $last_name = $node->get('field_last_name')->getValue()[0];
    $emailArray = $node->get('field_email')->getValue();
    foreach ($emailArray as $key => $value) {
      $email[$key] = $value;
    }
    $orcid = $node->get('field_orcid')->getValue()[0];
    $web_presence = $node->get('field_web_presence')->getValue();
    $professional_titles = $node->get('field_professional_titles')->getValue();
    $related_organizations_array = $node->get('field_related_organizations')->getValue();
    foreach ($related_organizations_array as $key => $value) {
      foreach ($value as $k2 => $v2) {
        $related_organizations[$key] = $v2;
      }
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
    // get organization webform and load values
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
    $document_type_array = $node->get('field_document_type')->getValue();
    foreach ($document_type_array as $key => $value) {
     $document_type[$key] = $value['target_id'];
    }
    $doi = $node->get('field_doi')->getValue();
    $external_resource_array = $node->get('field_external_resource')->getValue();
    foreach ($external_resource_array as $key => $value) {
      $external_resource[$key] = $value['uri'];
    }

    foreach ($node->field_file as $key => $file_paragraph) {
      $p = $file_paragraph->entity;

      foreach ($p->field_file_format->getValue() as $delta_format) {
        $file[$key]['file_format'] = $delta_format['target_id'];
      }
      foreach ($p->field_file_upload->getValue() as $delta_upload) {
        $file[$key]['file_upload'] = $delta_upload['target_id'];
      }
      foreach ($p->field_file_version_description->getValue() as $delta_description)  {
         $file[$key]['file_version_description'] = $delta_description['value'];
      }
      foreach ($p->field_format_version->getValue() as $delta_version)  {
         $file[$key]['format_version'] = $delta_version['value'];
      }
    }

    $license_array = $node->get('field_license')->getValue();
    foreach ($license_array as $key => $value) {
      $license[$key] = $value['target_id'];
    }

    foreach ($node->field_publication_info as $key => $pub_paragraph) {
      $p = $pub_paragraph->entity;

      foreach ($p->field_publication_date->getValue() as $delta_date) {
        $publication_info[$key]['publication_date'] = $delta_date['value'];
      }
      foreach ($p->field_publication_source->getValue() as $delta_source) {
        $publication_info[$key]['publication_source'] = $delta_source['uri'];
      }
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
    // get organization webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_document');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

}
