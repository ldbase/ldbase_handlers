<?php

namespace Drupal\ldbase_handlers;

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
    $messageTemplate = 'ldbase_taxonomy_term_added';
    $current_user = \Drupal::currentUser()->id();
    // get values for message arguments in template
    $taxonomy_term = $term->getName();
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($term->getVocabularyId())->label();

    $admin_user_ids = $this->getLdbaseAdministratorUserIds();
    // send a message to each admin
    foreach ($admin_user_ids as $admin_id) {
      // create a new message from template
      $message = $this->entityTypeManager->getStorage('message')->create(['template' => $messageTemplate, 'uid' => $admin_id]);
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
    $messageTemplate = 'ldbase_user_added_to_project';
    $current_user = \Drupal::currentUser()->id();
    $added_user_id = $entity->entity_id->target_id;
    $project_group_id = $entity->gid->target_id;
    $role_id = $entity->group_roles->target_id;

    $project_group = $this->entityTypeManager->getStorage('group')->load($project_group_id);
    $added_user = $this->entityTypeManager->getStorage('user')->load($added_user_id);
    $group_role = $this->entityTypeManager->getStorage('group_role')->load($role_id);

    // create a new message from template
    // Notify uses Message Author (uid) as "To" address
    $message = $this->entityTypeManager->getStorage('message')->create(['template' => $messageTemplate, 'uid' => $added_user_id]);
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
   * Get Ids of Users with the Administrator role
   */
  private function getLdbaseAdministratorUserIds() {
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'administrator')
      ->execute();

    return $ids;
  }

  /**
   * Get Group UserIds given a node and optional array of group role machine names
   */
  private function getGroupUserIdsByRoles(Node $node, array $groupRoles = NULL) {
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
