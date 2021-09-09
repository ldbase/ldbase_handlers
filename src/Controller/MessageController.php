<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

class MessageController extends ControllerBase {

  public function markMessageRead(Message $message, User $user) {
    $current_user = \Drupal::currentUser();
    if ($current_user->id() == $user->id()) {
      if ($message->hasField('field_message_status')) {
        $message->set('field_message_status', ["Read"]);
        $message->save();
      }
    }
    $response = new AjaxResponse();
    return $response;
  }

}
