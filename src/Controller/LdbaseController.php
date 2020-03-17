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
   * Loads Document Webform and associates project id
   *
   * The Project passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function addDocument(NodeInterface $node, $document_type = NULL) {
    $project_id = $node->id();

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
        'project_id' => $project_id,
        'document_type' => $doc_type,
      ]
    ];

    $operation = 'add';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_document');
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

  /**
   * Loads Dataset Webform and associates project id
   *
   * The Project passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function addDataset(NodeInterface $node) {
    $project_id = $node->id();
    $values = [
      'data' => [
        'project_id' => $project_id,
      ]
    ];

    $operation = 'add';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_dataset');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;

  }

  /**
   * Loads Dataset node data into a webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editDataset(NodeInterface $node) {

    // get node data
    $node_id = $node->id();
    $title = $node->getTitle();
    $doi = $node->get('field_doi')->getValue();
    $description = $node->get('body')->value;
    $contributors = $node->get('field_related_persons')->getValue();
    $host_organizations = $node->get('field_related_organizations')->getValue();
    $location = $node->get('field_location')->getValue();
    foreach ($node->get('field_component_skills')->getValue() as $delta => $value) {
      $constructs[$delta] = $value['target_id'];
    }
    $time_points = $node->get('field_time_points')->value;
    foreach ($node->get('field_data_collection_period')->getValue() as $delta => $value) {
      $data_collection_period[$delta]['start_date'] = $value['value'];
      $data_collection_period[$delta]['end_date'] = $value['end_value'];
    }
    foreach ($node->get('field_data_collection_locations')->getValue() as $delta => $value) {
       $data_collection_locations[$delta] = $value['target_id'];
    }
    foreach($node->get('field_assessment_name')->getValue() as $delta => $value) {
      $assessment_name[$delta] = $value['target_id'];
    }
    // demographics paragraph (participants)
    foreach ($node->field_demographics_information as $delta => $demographics_paragraph) {
      $p = $demographics_paragraph->entity;
      $participants[$delta]['number_of_participants'] = $p->field_number_of_participants->value;
      $participants[$delta]['participant_type'] = $p->field_participant_type->target_id;
      $participants[$delta]['age_range_from'] = $p->get('field_age_range')->from;
      $participants[$delta]['age_range_to'] = $p->get('field_age_range')->to;
    }
    foreach ($node->get('field_special_populations')->getValue() as $delta => $value) {
      $special_populations[$delta] = $value['target_id'];
    }
    foreach ($node->get('field_variable_types_in_dataset')->getValue() as $delta => $value) {
      $variable_types_in_dataset[$delta] = $value['target_id'];
    }
    $license = $node->get('field_license')->target_id;
    // file access paragraph
    foreach ($node->field_file_access_restrictions as $delta => $access_paragraph) {
      $p = $access_paragraph->entity;
      $file_access_restrictions[$delta]['file_embargoed'] = $p->field_file_embargoed->value == 1 ? 'Yes' : 'No';
      $file_access_restrictions[$delta]['embargo_expiry_date'] = $p->field_embaro_expiry_date->value;
      $file_access_restrictions[$delta]['allow_file_requests'] = $p->field_allow_file_requests->value == 1 ? 'Yes' : 'No';
    }
    $external_resource = $node->get('field_external_resource')->uri;
    // file paragraph
    foreach ($node->field_file as $delta => $file_paragraph) {
      $p = $file_paragraph->entity;
      $file[$delta]['file_format'] = $p->field_file_format->target_id;
      $file[$delta]['file_upload'] = $p->field_file_upload->entity->id();
      $file[$delta]['file_version_description'] = $p->field_file_version_description->value;
      $file[$delta]['format_version'] = $p->field_format_version->value;
    }
    // publication info paragraph
    foreach ($node->field_publication_info as $delta => $pub_paragraph) {
      $p = $pub_paragraph->entity;
      $publication_info[$delta]['publication_date'] = $p->field_publication_date->value;
      $publication_info[$delta]['publication_source'] = $p->get('field_publication_source')->uri;
    }
    $affiliated_code = $node->get('field_affiliated_code')->getValue();
    $affiliated_documents = $node->get('field_affiliated_documents')->getValue();
    $unaffiliated_citation = $node->get('field_unaffiliated_citation')->getValue();

    $values = [
      'data' => [
        'node_id' => $node_id,
        'title' => $title,
        'doi' => $doi,
        'description' => $description,
        'contributors' => $contributors,
        'host_organizations' => $host_organizations,
        'location' => $location,
        'constructs' => $constructs,
        'time_points' => $time_points,
        'data_collection_period' => $data_collection_period,
        'data_collection_locations' =>  $data_collection_locations,
        'assessment_name' => $assessment_name,
        'participants' => $participants,
        'special_populations' => $special_populations,
        'variable_types_in_dataset' => $variable_types_in_dataset,
        'license' => $license,
        'file_access_restrictions' => $file_access_restrictions,
        'external_resource' => $external_resource,
        'file' => $file,
        'publication_info' => $publication_info,
        'affiliated_code' => $affiliated_code,
        'affiliated_documents' => $affiliated_documents,
        'unaffiliated_citation' => $unaffiliated_citation,
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_dataset');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;

  }

  /**
   * Loads Project node data into its webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editProject(NodeInterface $node) {

    //get node data
    $node_id = $node->id();
    $title = $node->getTitle();
    $description = $node->get('body')->value;
    $related_persons = $node->get('field_related_persons')->getValue();
    $related_organizations = $node->get('field_related_organizations')->getValue();
    $doi = $node->get('field_doi')->getValue();
    foreach ($node->get('field_activity_range')->getValue() as $delta => $value) {
      $activity_range[$delta]['start_date'] = $value['value'];
      $activity_range[$delta]['end_date'] = $value['end_value'];
    }
    $website = $node->get('field_website')->getValue();
    //grant information paragraph
    foreach ($node->field_grant_information as $delta => $grant_paragraph) {
      $p = $grant_paragraph->entity;
      $grant_information[$delta]['funding_agency'] = $p->field_funding_agency->target_id;
      $grant_information[$delta]['grant_number'] = $p->field_grant_number->value;
    }
    foreach ($node->get('field_project_type')->getValue() as $delta => $value) {
      $project_type[$delta] = $value['target_id'];
    }
    foreach ($node->get('field_schooling')->getValue() as $delta => $value) {
      $schooling[$delta] = $value['target_id'];
    }
    foreach ($node->get('field_curricula')->getValue() as $delta => $value) {
      $curricula[$delta] = $value['target_id'];
    }
    foreach ($node->get('field_time_method')->getValue() as $delta => $value) {
      $time_method[$delta] = $value['target_id'];
    }
    $affiliated_datasets = $node->get('field_affiliated_datasets')->getValue();
    $affiliated_documents = $node->get('field_affiliated_documents')->getValue();
    $unaffiliated_citation = $node->get('field_unaffiliated_citation')->getValue();

    $values = [
      'data' => [
        'node_id' => $node_id,
        'title' => $title,
        'description' => $description,
        'related_persons' => $related_persons,
        'related_organizations' => $related_organizations,
        'doi' => $doi,
        'activity_range' =>$activity_range,
        'website' => $website,
        'grant_information' => $grant_information,
        'project_type' => $project_type,
        'schooling' => $schooling,
        'curricula' => $curricula,
        'time_method' => $time_method,
        'affiliated_datasets' => $affiliated_datasets,
        'affiliated_documents' => $affiliated_documents,
        'unaffiliated_citation' => $unaffiliated_citation,
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_project');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;

  }

}
