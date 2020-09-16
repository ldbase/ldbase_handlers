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

}
