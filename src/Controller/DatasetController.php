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
    foreach ($node->field_data_collection_range as $delta => $date_range_paragraph) {
      $p = $date_range_paragraph->entity;
      $data_collection_period[$delta]['start_month'] = $p->field_from_month->value;
      $data_collection_period[$delta]['start_year'] = $p->field_from_year->value;
      $data_collection_period[$delta]['end_month'] = $p->field_to_month->value;
      $data_collection_period[$delta]['end_year'] = $p->field_to_year->value;
      $data_collection_period[$delta]['period_target_id'] = $date_range_paragraph->target_id;
      $data_collection_period[$delta]['period_target_revision_id'] = $date_range_paragraph->target_revision_id;
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
      $participants[$delta]['participants_target_id'] = $demographics_paragraph->target_id;
      $participants[$delta]['participants_target_revision_id'] = $demographics_paragraph->target_revision_id;
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
    $dataset_upload_or_external = $node->get('field_dataset_upload_or_external')->value;
    $external_resource = $node->get('field_external_resource')->uri;

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

    // file metadata paragraph
    $file = [];
    foreach ($node->field_dataset_version as $delta => $file_metadata_paragraph) {
      $p = $file_metadata_paragraph->entity;
      $file[$delta]['dataset_version_format'] = $p->field_file_format->target_id;
      $file[$delta]['dataset_version_upload'] = $p->field_file_upload->entity->id();
      $file[$delta]['dataset_version_id'] = $p->field_file_version_id->value;
      $file[$delta]['dataset_version_label'] = $p->field_file_version_label->value;
      $file[$delta]['dataset_version_description'] = $p->field_file_version_description->value;
      $file[$delta]['dataset_version_target_id'] = $file_metadata_paragraph->target_id;
      $file[$delta]['dataset_version_target_revision_id'] = $file_metadata_paragraph->target_revision_id;
    }
    $latest_version = array_pop($file);
    $file = [$latest_version];

    //Set $embargoed
    //Set $embargo_expiry

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
        'dataset_upload_or_external' => $dataset_upload_or_external,
        'external_resource' => $external_resource,
        'publication_info' => $publication_info,
        'dataset_version' => $file,
        //'embargoed' => $embargoed,
        //'embargo_expiry' => $embargo_expiry,
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_dataset');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

}
