<?php

namespace Drupal\ldbase_handlers;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\message\Entity\Message;

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
    // get values for message arguments in template
    $taxonomy_term = $term->getName();
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($term->getVocabularyId())->label();

    // create a new message from template
    $message = $this->entityTypeManager->getStorage('message')->create(['template' => $messageTemplate]);
    // set arguments for term replacements
    $message->setArguments([
      '@taxonomy_term' => $taxonomy_term,
      '@vocabulary' => $vocabulary,
    ]);
    // save the message
    $message->save();

    return $message;  // can Notify use this?
  }

  public function userAddedToGroupMessage($entity) {
    $messageTemplate = 'ldbase_user_added_to_project';
    $added_user_id = $entity->entity_id->target_id;
    $project_group_id = $entity->gid->target_id;
    $role_id = $entity->group_roles->target_id;

    $project_group = $this->entityTypeManager->getStorage('group')->load($project_group_id);
    $added_user = $this->entityTypeManager->getStorage('user')->load($added_user_id);
    $group_role = $this->entityTypeManager->getStorage('group_role')->load($role_id);

    // create a new message from template
    $message = $this->entityTypeManager->getStorage('message')->create(['template' => $messageTemplate]);
    $message->set('field_to_user', $added_user_id);
    $message->set('field_group', $project_group_id);
    $message->setArguments([
      '@project_name' => $project_group->label(),
      '@group_role' => $group_role->label(),
    ]);
    $message->save();
  }

}
