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
    $added_user_id = $entity->entity_id->target_id;
    $project_group_id = $entity->gid->target_id;
    $role_id = $entity->group_roles->target_id;

    $project_group = $this->entityTypeManager->getStorage('group')->load($project_group_id);
    $added_user = $this->entityTypeManager->getStorage('user')->load($added_user_id);
    $group_role = $this->entityTypeManager->getStorage('group_role')->load($role_id);

    // create a new message from template
    // Notify uses Message Author (uid) as "To" address
    $message = $this->entityTypeManager->getStorage('message')->create(['template' => $message_template, 'uid' => $added_user_id]);
    $message->set('field_from_user', $current_user);
    $message->set('field_to_user', $added_user_id);
    $message->set('field_group', $project_group_id);
    $message->setArguments([
      '@project_name' => $project_group->label(),
      '@group_role' => $group_role->label(),
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
