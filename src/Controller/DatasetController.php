<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
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
    $parent_is_published = $node->status->value;
    $values = [
      'data' => [
        'passed_id' => $passed_id,
        'parent_is_published' => $parent_is_published,
      ]
    ];

    $operation = 'add';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_dataset');
    $webform = $webform->getSubmissionForm($values, $operation);
    // get manage members text or link (if access)
    $exempt_users_description = $this->getEmbargoExemptUsersDescription($node);
    $embargoed_description = $this->getEmbargoedDescription($node);
    // overwrite exempt users description
    $webform['elements']['embargo_exempt_users']['#description']['#markup'] = $exempt_users_description;
    // overwrite embargoed description
    $webform['elements']['embargoed']['#description']['#markup'] = $embargoed_description;

    if (!$parent_is_published) {
      $type = ucfirst($node->bundle());
      $title = $node->getTitle();
      $message = $this->t('You may save, but to publish this item you must first publish its parent %type: %title.', ['%type' => $type, '%title' => $title]);
      $webform['elements']['disabled_publish_message']['#message_message']['#markup'] = $message;
    }

    return $webform;
  }

  /**
   * Gets title for "Add Dataset to ..." page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getAddDatasetTitle(NodeInterface $node) {
    return 'Add Dataset to ' . ucfirst($node->getType()) . ': ' . $node->getTitle();
  }

  /**
   * Gets title for edit page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getEditTitle(NodeInterface $node) {
    return 'Edit Dataset: ' . $node->getTitle();
  }

  /**
   * Loads Dataset node data into a webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editDataset(NodeInterface $node) {

    // get node data
    $node_id = $node->id();
    $published_flag = $node->get('status')->value;
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
    if (empty($license)) {
      $license = $node->get('field_license_other')->value;
    }
    $dataset_upload_or_external = $node->get('field_dataset_upload_or_external')->value;
    $external_resource = $node->get('field_external_resource')->uri;

    // publication info paragraph
    /*$publication_info = [];
    foreach ($node->field_publication_info as $delta => $pub_paragraph) {
      $p = $pub_paragraph->entity;
      $publication_info[$delta]['publication_month'] = $p->field_publication_month->value;
      $publication_info[$delta]['publication_year'] = $p->field_publication_year->value;
      $publication_info[$delta]['publication_source'] = $p->get('field_publication_source')->uri;
      $publication_info[$delta]['publication_target_id'] = $pub_paragraph->target_id;
      $publication_info[$delta]['publication_target_revision_id'] = $pub_paragraph->target_revision_id;
    }*/

    // file metadata paragraph
    $file = [];
    foreach ($node->field_dataset_version as $delta => $file_metadata_paragraph) {
      $p = $file_metadata_paragraph->entity;
      $file[$delta]['dataset_version_format'] = $p->field_file_format->target_id;
      $file[$delta]['dataset_version_upload'] = $p->field_file_upload->entity->id();
      $file[$delta]['dataset_version_id'] = $p->field_file_version_id->value;
      //$file[$delta]['dataset_version_label'] = $p->field_file_version_label->value;
      $file[$delta]['dataset_version_description'] = $p->field_file_version_description->value;
      $file[$delta]['dataset_version_target_id'] = $file_metadata_paragraph->target_id;
      $file[$delta]['dataset_version_target_revision_id'] = $file_metadata_paragraph->target_revision_id;
    }
    $latest_version = array_pop($file);
    $file = [$latest_version];
    $passed_id = $node->get('field_affiliated_parents')->target_id;
    $user_agreement = $node->get('field_user_agreement')->value;

    //Set $embargoed
    //Set $embargo_expiry
    $embargo_id = \Drupal::service('ldbase_embargoes.embargoes')->getAllEmbargoesByNids(array($node->id()));
    $embargo = !empty($embargo_id) ? \Drupal::entityTypeManager()->getStorage('node')->load($embargo_id[0]) : '';
    $embargoed = !empty($embargo);
    $embargo_expiry = empty($embargo) ? '' : $embargo->get('field_expiration_date')->value;
    $embargo_exempt_users = empty($embargo) ? [] : $embargo->get('field_exempt_users')->getValue();

    // Data unique or derived
    $dataset_unique = $node->get('field_data_unique_or_derived')->value;
    $derivation_source = $node->get('field_derivation_source')->getValue();

    $harmonized_dataset = $node->get('field_harmonized_dataset')->value;

    // is parent node published?
    $parent_node = \Drupal::entityTypeManager()->getStorage('node')->load($passed_id);
    $parent_is_published = $parent_node->status->value;

    $values = [
      'data' => [
        'node_id' => $node_id,
        'published_flag' => $published_flag,
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
        //'publication_info' => $publication_info,
        'dataset_version' => $file,
        'user_agreement' => $user_agreement,
        'embargoed' => $embargoed,
        'embargo_expiry' => $embargo_expiry,
        'embargo_exempt_users' => $embargo_exempt_users,
        'dataset_unique' => $dataset_unique,
        'derivation_source' => $derivation_source,
        'harmonized_dataset' => $harmonized_dataset,
        'passed_id' => $passed_id,
        'parent_is_published' => $parent_is_published,
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_dataset');
    $webform = $webform->getSubmissionForm($values, $operation);
    // get manage members text or link (if access)
    $exempt_users_description = $this->getEmbargoExemptUsersDescription($node);
    $embargoed_description = $this->getEmbargoedDescription($node);
    // overwrite exempt users description
    $webform['elements']['embargo_exempt_users']['#description']['#markup'] = $exempt_users_description;
    // overwrite embargoed description
    $webform['elements']['embargoed']['#description']['#markup'] = $embargoed_description;

    //overwrite doi descirption
    if (!empty($doi)) {
      $webform['elements']['generate_a_doi']['#access'] = false;
    }

    if (!$parent_is_published) {
      $type = ucfirst($parent_node->bundle());
      $title = $parent_node->getTitle();
      $message = $this->t('You may save, but to publish this item you must first publish its parent %type: %title.', ['%type' => $type, '%title' => $title]);
      $webform['elements']['disabled_publish_message']['#message_message']['#markup'] = $message;
    }

    return $webform;
  }

  /**
   * Loads a dataset version into its webform for editing
   *
   * @param Drupal\paragraphs\Entity\Paragraph $paragraph
   */
  public function editVersion(Paragraph $paragraph) {
    $dataset_version = [];
    $dataset_version[0]['dataset_version_format'] = $paragraph->field_file_format->target_id;
    $dataset_version[0]['dataset_version_upload'] = $paragraph->field_file_upload->entity->id();
    $dataset_version[0]['dataset_version_id'] = $paragraph->field_file_version_id->value;
    //$dataset_version[0]['dataset_version_label'] = $paragraph->field_file_version_label->value;
    $dataset_version[0]['dataset_version_description'] = $paragraph->field_file_version_description->value;
    $dataset_version[0]['dataset_version_target_id'] = $paragraph->id->value;
    $dataset_version[0]['dataset_version_target_revision_id'] = $paragraph->revision_id->value;

    $values = [
      'data' => [
        'dataset_version' => $dataset_version,
      ]
    ];

    $operation = 'edit';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('edit_dataset_version');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

  /**
   * create description text with link for embargo_exemt_users field
   */
  private function getEmbargoExemptUsersDescription(NodeInterface $node) {
    $manage_members_link = $this->getManageMembersLink($node, true);
    // create exempt users field description
    $exempt_users_description = "When you restrict public access to your data, {$manage_members_link} are the only people who can view those files. You can, however, allow certain individuals, such as a collaborator or a data requester whom you approve of, to override the restriction to view/download the data. Simply enter their LDbase name below. They will then gain access to this data, but they will not be able to to perform any actions that your Project Administrators and Project Editors can do.";

    return $exempt_users_description;
  }

  /**
   * create description text with link for embargoed field
   */
  private function getEmbargoedDescription(NodeInterface $node) {
    $manage_members_link = $this->getManageMembersLink($node, false);
    $embargoed_description = "Checking this box will make the submitted files unavailable to anyone who is not a {$manage_members_link} of your project, but the dataset metadata will still be public.";

    return $embargoed_description;
  }

  /**
   * Return link to Manage Group Members page, or text if no access
   */
  private function getManageMembersLink(NodeInterface $node, $plural) {
    $nid = $node->id();
    // get top project uuid
    $project = \Drupal::service('ldbase.object_service')->getLdbaseRootProjectNodeFromLdbaseObjectNid($nid);
    // get group id
    $group_contents = GroupContent::loadByEntity($project);
    $group = array_pop($group_contents)->getGroup();

    // create link
    $route_name = 'view.group_members.ldbase_project';
    if ($plural) {
      $link_text = "Project Administrators and Project Editors";
    } else {
      $link_text = "Project Administrator or Project Editor";
    }

    $link_url = Url::fromRoute($route_name, ['node' => $project->uuid(), 'group' => $group->id()], ['attributes' => ['target' => '_blank']]);
    $rendered_link = Link::fromTextAndUrl($link_text, $link_url);
    // if user has access return link, otherwise return text
    $manage_members_link = $link_url->access($this->currentUser()) ? $rendered_link->toString() : $link_text;

    return $manage_members_link;
  }

}
