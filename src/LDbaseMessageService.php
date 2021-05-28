<?php

namespace Drupal\ldbase_handlers;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\message\Entity\Message;
use Drpal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;

/**
 * Class LDbaseMessageService.
 */
class LDbaseMessageService implements LDbaseMessageServiceInterface {

  protected $entityTypeManager;

  /**
   * Constructs a new LDbaseMessageService object.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function newTermAddedMessage($term) {
    // machine name of message template for new terms
    $message_template = 'ldbase_taxonomy_term_added';
    $current_user = \Drupal::currentUser()->id();
    // get values for message arguments in template
    $taxonomy_term = $term->getName();
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($term->getVocabularyId())->label();

    $admin_user_ids = $this->getLdbaseAdministratorUserIds();
    // send a message to each admin
    foreach ($admin_user_ids as $admin_id) {
      // create a new message from template
      $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $admin_id]);
      $message->set('field_from_user', $current_user);
      $message->set('field_to_user', $admin_id);
      // set arguments for term replacements
      $message->setArguments([
        '@taxonomy_term' => $taxonomy_term,
        '@vocabulary' => $vocabulary,
      ]);

      // save the message
      $message->save();

      // send notification
      $this->sendLdbaseMessage($message);
    }
  }

  public function userAddedToGroupMessage($entity) {
    $message_template = 'ldbase_user_added_to_project';
    $current_user = \Drupal::currentUser()->id();
    // entity is the group membership itself
    $added_user_id = $entity->entity_id->target_id;
    $project_group_id = $entity->gid->target_id;
    $role_id = $entity->group_roles->target_id;
    // get the project group content entity for this group
    $group_project = $this->entityTypeManager->getStorage('group_content')
      ->loadByProperties(['gid' => $project_group_id, 'type' => 'project_group-group_node-project']);
    // get the project node id from the group content entity
    $project_nid = $group_project[key($group_project)]->entity_id->target_id;
    // get the project node
    $project = $this->entityTypeManager->getStorage('node')->load($project_nid);
    $ldbase_object = ucfirst($project->bundle());
    $ldbase_object_title = $project->getTitle();
    $link_route = 'entity.node.canonical';
    $link_url = Url::fromRoute($link_route, ['node' => $project->id()]);
    $link_text = $ldbase_object . ': ' . $ldbase_object_title;
    $link_to_object = Link::fromTextAndUrl($link_text, $link_url)->toString();
    $added_user = $this->entityTypeManager->getStorage('user')->load($added_user_id);
    $group_role = $this->entityTypeManager->getStorage('group_role')->load($role_id);

    // create a new message from template
    // Notify uses Message Author (uid) as "To" address
    $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $added_user_id]);
    $message->set('field_from_user', $current_user);
    $message->set('field_to_user', $added_user_id);
    $message->set('field_group', $project_group_id);
    $message->setArguments([
      '@link_to_object' => $link_to_object,
      '@group_role' => $group_role->label(),
    ]);

    $message->save();

    // send email notification
    $this->sendLdbaseMessage($message);
  }

  /**
   * Send message when content has remained unpublished
   */
  public function contentUnpublishedReminder($node) {
    $message_template = 'ldbase_unpublished_content';
    $current_user = \Drupal::currentUser()->id();

    $ldbase_object = ucfirst($node->bundle());
    $ldbase_object_title = $node->getTitle();
    $link_to_object_route = 'entity.node.canonical';
    $link_to_object_url = Url::fromRoute($link_to_object_route, ['node' => $node->id()], ['absolute' => TRUE]);
    $link_to_object_text = $ldbase_object . ': ' . $ldbase_object_title;
    $link_to_object = Link::fromTextAndUrl($link_to_object_text, $link_to_object_url)->toString();

    $group_admins = $this->getGroupUserIdsByRoles($node, ['project_group-administrator']);
    // create a new message from template
    // Notify uses Message Author (uid) as "To" address
    foreach ($group_admins as $admin_id) {
      $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $admin_id]);
      $message->set('field_from_user', $current_user);
      $message->set('field_to_user', $admin_id);
      $message->setArguments([
        '@link_to_object' => $link_to_object,
      ]);

      $message->save();

      // send email notification
      $this->sendLdbaseMessage($message);
    }
  }

    /**
   * send message when an existing record request is submitted
   */
  public function existingRecordRequestMade($new_request_id) {
    $message_template = 'ldbase_existing_record_request';
    $current_user = \Drupal::currentUser()->id();
    $existing_record_request = $this->entityTypeManager->getStorage('node')->load($new_request_id);
    $content_id = $existing_record_request->field_requested_node_link->target_id;
    $content_entity = $this->entityTypeManager->getStorage('node')->load($content_id);
    $group_contents = GroupContent::loadByEntity($content_entity);
    $group_content = reset($group_contents); // content can only belong to one group
    $group_id = $group_content->getGroup()->id();
    $link_route = 'view.existing_records_requests.page_1';
    $link_url = Url::fromRoute($link_route, ['group' => $group_id]);
    $link_text = 'View Existing Records Requests';
    $existing_records_requests_link = Link::fromTextAndUrl($link_text, $link_url)->toString();
    $ldbase_object = ucfirst($content_entity->bundle());
    $ldbase_object_title = $content_entity->getTitle();
    $link_to_object_route = 'entity.node.canonical';
    $link_to_object_url = Url::fromRoute($link_to_object_route, ['node' => $content_id]);
    $link_to_object_text = $ldbase_object . ': ' . $ldbase_object_title;
    $link_to_object = Link::fromTextAndUrl($link_to_object_text, $link_to_object_url)->toString();
    $real_person_id = $existing_record_request->field_requesting_person->target_id;
    $real_person_node = $this->entityTypeManager->getStorage('node')->load($real_person_id);
    $real_user_id = $real_person_node->field_drupal_account_id->target_id;

    $group_admins = $this->getGroupUserIdsByRoles($content_entity, ['project_group-administrator']);
    // create a new message from template
    // Notify uses Message Author (uid) as "To" address
    foreach ($group_admins as $admin_id) {
      $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $admin_id]);
      $message->set('field_from_user', $real_user_id);
      $message->set('field_to_user', $admin_id);
      $message->set('field_group', $group_id);
      $message->setArguments([
        '@link_to_object' => $link_to_object,
        '@existing_records_requests_link' => $existing_records_requests_link,
        '@user' => $real_person_node->getTitle(),
      ]);

      $message->save();

      // send email notification
      $this->sendLdbaseMessage($message);
    }
  }

  /**
   * notify user of possible matches found in batch
   */
  public function possibleMatchesNotification($user_id) {
    $message_template = 'ldbase_possible_duplicate_person';
    $current_user = \Drupal::currentUser()->id();
    $link_route = 'view.possible_account_matches.page_1';
    $link_url = Url::fromRoute($link_route,[],['absolute' => TRUE]);
    $link_text = 'Possible Account Matches';
    $possible_matches_link = Link::fromTextAndUrl($link_text, $link_url)->toString();

    // create a new message from template
    // Notify uses Message Author (uid) as "To" address
    $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $user_id]);
    $message->set('field_from_user', $current_user);
    $message->set('field_to_user', $user_id);
    $message->setArguments([
      '@possible_matches_link' => $possible_matches_link,
    ]);

    $message->save();

    // send email notification
    $this->sendLdbaseMessage($message);
  }

  /**
   * Send message when User is added as a contributor (field_related_persons)
   */
  public function contributorAddedMessage($added_user_id, $node) {
    $message_template = 'ldbase_user_added_as_contributor';
    $current_user = \Drupal::currentUser();
    $ldbase_object = ucfirst($node->bundle());
    $ldbase_object_title = $node->getTitle();
    $link_route = 'entity.node.canonical';
    $link_url = Url::fromRoute($link_route, ['node' => $node->id()]);
    $link_text = $ldbase_object . ': ' . $ldbase_object_title;
    $link_to_object = Link::fromTextAndUrl($link_text, $link_url)->toString();
    // create a new message from template
    // Notify uses Message Author (uid) as "To" address
    $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $added_user_id]);
    $message->set('field_from_user', $current_user->id());
    $message->set('field_to_user', $added_user_id);
    $message->setArguments([
      '@link_to_object' => $link_to_object,
      '@user' => $current_user->getDisplayName(),
    ]);

    $message->save();

    // send email notification
    $this->sendLdbaseMessage($message);
  }

  /**
   * send message to subscribers when a dataset file has been updated.
   */
  public function datasetHasBeenUpdated($dataset_node) {
    $subscribers = $dataset_node->field_subscribed_users->getValue();
    if (!empty($subscribers)) {
      $message_template = 'ldbase_dataset_update_message';
      $current_user = \Drupal::currentUser()->id();

      $ldbase_object_title = $dataset_node->getTitle();
      $link_route = 'entity.node.canonical';
      $link_text = $dataset_node->getTitle();
      $link_url = Url::fromRoute($link_route, ['node' => $dataset_node->id()]);
      $dataset_link = Link::fromTextAndUrl($link_text, $link_url)->toString();

      foreach ($subscribers as $subscriber) {
        $user_id = $subscriber['target_id'];
        // create a new message from template
        // Notify uses Message Author (uid) as "To" address
        $message = $this->entityTypeManager
          ->getStorage('message')
          ->create(['template' => $message_template, 'uid' => $user_id]);

        $message->set('field_from_user', $current_user);
        $message->set('field_to_user', $user_id);
        $message->setArguments([
          '@link_to_dataset' => $dataset_link,
        ]);
        $message->save();

        // send email notification
        $this->sendLdbaseMessage($message);
      }
    }
  }


  /**
   * send message when a user indicates they would like to contribute to the harmonized dataset.
   */
  public function harmonizedDatasetMessage($dataset_node) {
    $message_template = 'ldbase_harmonized_dataset';
    $current_user = \Drupal::currentUser();

    $person = $this->entityTypeManager->getStorage('node')->loadByProperties(['field_drupal_account_id' => $current_user->id()]);
    $user_name = !empty($person) ? array_values($person)[0]->getTitle() : '';

    $ldbase_object_title = $dataset_node->getTitle();
    $link_route = 'entity.node.canonical';
    $link_text = $dataset_node->getTitle();
    $link_url = Url::fromRoute($link_route, ['node' => $dataset_node->id()]);
    $dataset_link = Link::fromTextAndUrl($link_text, $link_url)->toString();

    $admin_user_ids = $this->getLdbaseAdministratorUserIds();
    // send a message to each admin
    foreach ($admin_user_ids as $admin_id) {
      // create a new message from template
      $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $admin_id]);
      $message->set('field_from_user', $current_user->id());
      $message->set('field_to_user', $admin_id);
      // set arguments for term replacements
      $message->setArguments([
        '@user_name' => $user_name,
        '@user_email' => $current_user->getDisplayName(),
        '@link_to_dataset_information' => $dataset_link,
      ]);
      // save the message
      $message->save();

      // send notification
      $this->sendLdbaseMessage($message);
    }
  }

  /**
   * Send message from the Report a Problem Form
   */
  public function reportProblemMessage(array $message_data) {
    $message_template = 'ldbase_report_a_problem';
    $current_user = \Drupal::currentUser();
    $admin_user_ids = $this->getLdbaseAdministratorUserIds();
    // send a message to each admin
    foreach ($admin_user_ids as $admin_id) {
      // create a new message from template
      $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $admin_id]);
      $message->set('field_from_user', $current_user->id());
      $message->set('field_to_user', $admin_id);
      // set arguments for term replacements
      $message->setArguments([
        '@name' => $message_data['name'],
        '@email' => $message_data['email'],
        '@reply_requested' => $message_data['reply_requested'],
        '@issue' => $message_data['issue'],
        '@url' => $message_data['url']
      ]);
      // save the message
      $message->save();

      // send notification
      $this->sendLdbaseMessage($message);
    }
  }

  /**
   * Send message to authors when a taxonomy term is replaced during review
   * $message_array = [
   *   'old_term_name' => $old_term_name,
   *   'new_term_name' => $new_term_name,
   *   'reason_for_change' => $message_to_users,
   *   'message_nodes' => ['nid' => $node->id(),],
   *  ];
   */
  public function taxonomyTermChanged(array $message_array) {
    $message_template = 'ldbase_taxonomy_term_changed';
    $current_user = \Drupal::currentUser();

    foreach ($message_array['message_nodes'] as $value) {
      $node = $this->entityTypeManager->getStorage('node')->load($value['nid']);
      $node_author = $node->getOwnerId();
      $ldbase_object = ucfirst($node->bundle());
      $ldbase_object_title = $node->getTitle();
      $link_route = 'entity.node.canonical';
      $link_url = Url::fromRoute($link_route, ['node' => $node->id()]);
      $link_text = $ldbase_object . ': ' . $ldbase_object_title;
      $link_to_object = Link::fromTextAndUrl($link_text, $link_url)->toString();
      // create a new message from template
      $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $node_author]);
      $message->set('field_from_user', $current_user->id());
      $message->set('field_to_user', $node_author);
      // set arguments for term replacements
      $message->setArguments([
        '@old_term_name' => $message_array['old_term_name'],
        '@new_term_name' => $message_array['new_term_name'],
        '@reason_for_change' => $message_array['reason_for_change'],
        '@link_to_object' => $link_to_object,
      ]);
      // save the message
      $message->save();
      // send notification
      $this->sendLdbaseMessage($message);
    }
  }

  /**
   * Get Ids of Users with the Administrator or FCRR Admin role
   */
  private function getLdbaseAdministratorUserIds() {
    $query = \Drupal::entityQuery('user');
    $roles = $query->orConditionGroup()->condition('roles', 'administrator')->condition('roles', 'fcrr_admin');
    $ids = $query
      ->condition($roles)
      ->condition('status', 1)
      ->execute();

    return $ids;
  }

  /**
   * Get Group UserIds given a node and optional array of group role machine names
   */
  public function getGroupUserIdsByRoles(Node $node, array $groupRoles = NULL) {
    // get node's group
    $group_contents = GroupContent::loadByEntity($node);
    $node_group = array_pop($group_contents)->getGroup();

    $group_members = $node_group->getMembers($groupRoles);
    $ids = [];
    foreach ($group_members as $member) {
      array_push($ids, $member->getUser()->uid->value);
    }

    return $ids;
  }

  /**
   * Calls the Notifier service and sends message
   */
  private function sendLdbaseMessage($message) {
    $notifier = \Drupal::service('message_notify.sender');
    $notifier->send($message);
  }

}
