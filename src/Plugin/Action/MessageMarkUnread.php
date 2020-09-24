<?php

namespace Drupal\ldbase_handlers\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Mark message as Unread.
 *
 * @Action(
 *   id = "message_mark_unread",
 *   label = @Translation("Mark message as Unread"),
 *   type = "message"
 * )
 */
class MessageMarkUnread extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($message = NULL) {
    /** \Drupal\message\MessageInterface $message */
    if ($message->hasField('field_message_status')) {
      $message->set('field_message_status', ["Unread"]);
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
