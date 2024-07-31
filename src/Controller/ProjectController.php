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
   * Gets title for edit page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getEditTitle(NodeInterface $node) {
    return 'Edit Project: ' . $node->getTitle();
  }

  /**
   * Loads Project node data into its webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editProject(NodeInterface $node) {
    //get node data
    $node_id = $node->id();
    $published_flag = $node->status->value;
    $do_not_contact_flag = $node->field_do_not_contact->value;
    $title = $node->getTitle();
    $description = $node->get('body')->value;
    $related_persons = $node->get('field_related_persons')->getValue();
    $related_organizations = $node->get('field_related_organizations')->getValue();
    $doi = $node->get('field_doi')->getValue();
    // date range selection paragraph
    $activity_range = [];
    foreach ($node->field_activity_range_select as $delta => $date_range_paragraph) {
      $p = $date_range_paragraph->entity;
      $activity_range[$delta]['from_month'] = $p->field_from_month->value;
      $activity_range[$delta]['from_year'] =  $p->field_from_year->value;
      $activity_range[$delta]['to_month'] = $p->field_to_month->value;
      $activity_range[$delta]['to_year'] =  $p->field_to_year->value;
      $activity_range[$delta]['activity_range_target_id'] = $date_range_paragraph->target_id;
      $activity_range[$delta]['activity_range_target_revision_id'] =  $date_range_paragraph->target_revision_id;
    }
    $website = $node->get('field_website')->getValue();
    // project logo
    $project_logo = $node->field_project_logo->entity;
    $project_logo_fid = !empty($project_logo) ? $project_logo->id() : NULL;

    //grant information paragraph
    $grant_information = [];
    foreach ($node->field_grant_information as $delta => $grant_paragraph) {
      $p = $grant_paragraph->entity;
      $grant_information[$delta]['funding_agency'] = $p->field_funding_agency->target_id;
      $grant_information[$delta]['grant_number'] = $p->field_grant_number->value;
      $grant_information[$delta]['grant_target_id'] = $grant_paragraph->target_id;
      $grant_information[$delta]['grant_target_revision_id'] = $grant_paragraph->target_revision_id;
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
        'published_flag' => $published_flag,
        'title' => $title,
        'description' => $description,
        'related_persons' => $related_persons,
        'related_organizations' => $related_organizations,
        'doi' => $doi,
        'activity_range' =>$activity_range,
        'website' => $website,
        'project_logo' => $project_logo_fid,
        'grant_information' => $grant_information,
        'project_type' => $project_type,
        'schooling' => $schooling,
        'curricula' => $curricula,
        'time_method' => $time_method,
        'do_not_contact_flag' => $do_not_contact_flag,
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_project');
    $webform = $webform->getSubmissionForm($values, $operation);

    //overwrite doi descirption
    if (!empty($doi)) {
      $webform['elements']['generate_a_doi']['#access'] = false;
    }
    return $webform;

  }

}
