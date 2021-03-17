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

class CodeController extends ControllerBase {

  /**
   * CodeController constructor
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
   * Gets title for "Add Code to ..." page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getAddCodeTitle(NodeInterface $node) {
    return 'Add Code to ' . ucfirst($node->getType()) . ': ' . $node->getTitle();
  }

  /**
   * Gets title for edit page
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function getEditTitle(NodeInterface $node) {
    return 'Edit Code: ' . $node->getTitle();
  }

  /**
   * Loads code node data into a webform for editing
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editCode(NodeInterface $node) {
    // get node data
    $nid = $node->id();
    $published_flag = $node->status->value;
    $title = $node->getTitle();
    $description = $node->get('body')->value;
    $authors = $node->get('field_related_persons')->getValue();
    $code_type = [];
    foreach ($node->get('field_code_type')->getValue() as $delta => $value) {
      $code_type[$delta] = $value['target_id'];
    }
    $doi = $node->get('field_doi')->getValue();
    $code_upload_or_external = $node->get('field_code_upload_or_external')->value;
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

    $code_file = $node->field_code_file->entity;
    $code_file_id = !empty($code_file) ? $code_file->id() : NULL;

    $passed_id = $node->get('field_affiliated_parents')->target_id;

    //Set $embargoed
    //Set $embargo_expiry
    $embargo_id = \Drupal::service('ldbase_embargoes.embargoes')->getAllEmbargoesByNids(array($node->id()));
    $embargo = !empty($embargo_id) ? \Drupal::entityTypeManager()->getStorage('node')->load($embargo_id[0]) : '';
    $embargoed = !empty($embargo);
    $embargo_expiry = empty($embargo) ? '' : $embargo->get('field_expiration_date')->value;
    $embargo_exempt_users = empty($embargo) ? [] : $embargo->get('field_exempt_users')->getValue();

    // is parent node published?
    $parent_node = \Drupal::entityTypeManager()->getStorage('node')->load($passed_id);
    $parent_is_published = $parent_node->status->value;

    $values = [
      'data' => [
        'node_id' => $nid,
        'published_flag' => $published_flag,
        'title' => $title,
        'description' => $description,
        'authors' => $authors,
        'code_type' => $code_type,
        'doi' => $doi,
        'code_upload_or_external' => $code_upload_or_external,
        'external_resource' => $external_resource,
        'license' => $license,
        'publication_info' => $publication_info,
        'code_file' => $code_file_id,
        'passed_id' => $passed_id,
        'embargoed' => $embargoed,
        'embargo_expiry' => $embargo_expiry,
        'embargo_exempt_users' => $embargo_exempt_users,
        'parent_is_published' => $parent_is_published,
      ]
    ];

    $operation = 'edit';

    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_code');
    $webform = $webform->getSubmissionForm($values,$operation);
    // get manage members text or link (if access)
    $exempt_users_description = $this->getEmbargoExemptUsersDescription($node);
    $embargoed_description = $this->getEmbargoedDescription($node);
    // overwrite exempt users description
    $webform['elements']['embargo_exempt_users']['#description']['#markup'] = $exempt_users_description;
    // overwrite embargoed description
    $webform['elements']['embargoed']['#description']['#markup'] = $embargoed_description;

    if (!$parent_is_published) {
      $type = ucfirst($parent_node->bundle());
      $title = $parent_node->getTitle();
      $message = $this->t('You may save, but to publish this item you must first publish its parent %type: %title.', ['%type' => $type, '%title' => $title]);
      $webform['elements']['disabled_publish_message']['#message_message']['#markup'] = $message;
    }

    return $webform;
  }

  /**
   * Loads Code Webform and associates dataset id
   *
   * The dataset passed in
   * @param \Drupal\Node\NodeInterface $node
   */
  public function addCode(NodeInterface $node) {
    $node_type = $node->getType();
    $passed_id = $node->id();
    $parent_is_published = $node->status->value;
    $published_flag = $parent_is_published;

    $values = [
      'data' => [
        'passed_id' => $passed_id,
        'parent_is_published' => $parent_is_published,
        'published_flag' => $published_flag,
      ]
    ];

    $operation = 'add';
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_code');
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
    $embargoed_description = "Checking this box will make the submitted files unavailable to anyone who is not a {$manage_members_link} of your project, but the code metadata will still be public.";

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
