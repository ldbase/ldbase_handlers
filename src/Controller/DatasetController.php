<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DatasetController extends ControllerBase {

  /**
   * DatasetController constructor
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
   * Loads Dataset Webform and associates project id
   *
   * The Project passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function addDataset(NodeInterface $node) {
    $passed_id = $node->id();
    $values = [
      'data' => [
        'passed_id' => $passed_id,
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
    $constructs = [];
    foreach ($node->get('field_component_skills')->getValue() as $delta => $value) {
      $constructs[$delta] = $value['target_id'];
    }
    $time_points = $node->get('field_time_points')->value;
    $data_collection_period = [];
    foreach ($node->get('field_data_collection_period')->getValue() as $delta => $value) {
      $data_collection_period[$delta]['start_date'] = $value['value'];
      $data_collection_period[$delta]['end_date'] = $value['end_value'];
    }
    $data_collection_locations = [];
    foreach ($node->get('field_data_collection_locations')->getValue() as $delta => $value) {
       $data_collection_locations[$delta] = $value['target_id'];
    }
    $assessment_name = [];
    foreach($node->get('field_assessment_name')->getValue() as $delta => $value) {
      $assessment_name[$delta] = $value['target_id'];
    }
    // demographics paragraph (participants)
    $participants = [];
    foreach ($node->field_demographics_information as $delta => $demographics_paragraph) {
      $p = $demographics_paragraph->entity;
      $participants[$delta]['number_of_participants'] = $p->field_number_of_participants->value;
      $participants[$delta]['participant_type'] = $p->field_participant_type->target_id;
      $participants[$delta]['age_range_from'] = $p->get('field_age_range')->from;
      $participants[$delta]['age_range_to'] = $p->get('field_age_range')->to;
    }
    $special_populations = [];
    foreach ($node->get('field_special_populations')->getValue() as $delta => $value) {
      $special_populations[$delta] = $value['target_id'];
    }
    $variable_types_in_dataset = [];
    foreach ($node->get('field_variable_types_in_dataset')->getValue() as $delta => $value) {
      $variable_types_in_dataset[$delta] = $value['target_id'];
    }
    $license = $node->get('field_license')->target_id;
    // file access paragraph
    $file_access_restrictions = [];
    foreach ($node->field_file_access_restrictions as $delta => $access_paragraph) {
      $p = $access_paragraph->entity;
      $file_access_restrictions[$delta]['file_embargoed'] = $p->field_file_embargoed->value == 1 ? 'Yes' : 'No';
      $file_access_restrictions[$delta]['embargo_expiry_date'] = $p->field_embaro_expiry_date->value;
      $file_access_restrictions[$delta]['allow_file_requests'] = $p->field_allow_file_requests->value == 1 ? 'Yes' : 'No';
    }
    $external_resource = $node->get('field_external_resource')->uri;
    // file paragraph
    $file = [];
    foreach ($node->field_file as $delta => $file_paragraph) {
      $p = $file_paragraph->entity;
      $file[$delta]['file_format'] = $p->field_file_format->target_id;
      $file[$delta]['file_upload'] = $p->field_file_upload->entity->id();
      $file[$delta]['file_version_description'] = $p->field_file_version_description->value;
      $file[$delta]['format_version'] = $p->field_format_version->value;
    }
    // publication info paragraph
    $publication_info = [];
    foreach ($node->field_publication_info as $delta => $pub_paragraph) {
      $p = $pub_paragraph->entity;
      $publication_info[$delta]['publication_date'] = $p->field_publication_date->value;
      $publication_info[$delta]['publication_source'] = $p->get('field_publication_source')->uri;
    }
    $affiliated_code = $node->get('field_affiliated_code')->getValue();
    $affiliated_datasets = $node->get('field_affiliated_datasets')->getValue();
    $affiliated_documents = $node->get('field_affiliated_documents')->getValue();
    $unaffiliated_citation = $node->get('field_unaffiliated_citation')->getValue();

    $affiliated_parents = $node->get('field_affiliated_parents')->getValue();
    $unaffiliated_parents = $node->get('field_unaffiliated_parents')->getValue();

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
        'affiliated_datasets' => $affiliated_datasets,
        'affiliated_documents' => $affiliated_documents,
        'unaffiliated_citation' => $unaffiliated_citation,
        'affiliated_parents' => $affiliated_parents,
        'unaffiliated_parents' => $unaffiliated_parents,
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_dataset');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

}
