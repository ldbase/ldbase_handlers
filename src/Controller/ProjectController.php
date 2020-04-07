<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProjectController extends ControllerBase {

  /**
   * ProjectController constructor
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
    $activity_range = [];
    foreach ($node->get('field_activity_range')->getValue() as $delta => $value) {
      $activity_range[$delta]['start_date'] = $value['value'];
      $activity_range[$delta]['end_date'] = $value['end_value'];
    }
    $website = $node->get('field_website')->getValue();
    //grant information paragraph
    $grant_information = [];
    foreach ($node->field_grant_information as $delta => $grant_paragraph) {
      $p = $grant_paragraph->entity;
      $grant_information[$delta]['funding_agency'] = $p->field_funding_agency->target_id;
      $grant_information[$delta]['grant_number'] = $p->field_grant_number->value;
    }
    $project_type = [];
    foreach ($node->get('field_project_type')->getValue() as $delta => $value) {
      $project_type[$delta] = $value['target_id'];
    }
    $schooling = [];
    foreach ($node->get('field_schooling')->getValue() as $delta => $value) {
      $schooling[$delta] = $value['target_id'];
    }
    $curricula = [];
    foreach ($node->get('field_curricula')->getValue() as $delta => $value) {
      $curricula[$delta] = $value['target_id'];
    }
    $time_method = [];
    foreach ($node->get('field_time_method')->getValue() as $delta => $value) {
      $time_method[$delta] = $value['target_id'];
    }

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
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_project');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;

  }

}
