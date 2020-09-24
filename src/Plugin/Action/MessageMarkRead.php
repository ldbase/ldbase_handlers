<?php

namespace Drupal\ldbase_handlers\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Mark message as Read.
 *
 * @Action(
 *   id = "message_mark_read",
 *   label = @Translation("Mark message as Read"),
 *   type = "message"
 * )
 */
class MessageMarkRead extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($message = NULL) {
    /** \Drupal\message\MessageInterface $message */
    if ($message->hasField('field_message_status')) {
      $message->set('field_message_status', ["Read"]);
      $message->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // return $account->hasPermission('overview messages');
    return TRUE;
  }

}
