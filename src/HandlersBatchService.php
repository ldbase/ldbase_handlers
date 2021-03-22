<?php

namespace Drupal\ldbase_handlers;

use Drupal\node\Entity\Node;

/**
 * Class HandlersBatchService.
 */
class HandlersBatchService {
  /**
   * Batch process callback
   *
   * @param int $id
   *  Id of the batch.
   * @param int $nid
   *  ID of the node to process
   * @param string $operation_details
   *  Details of the operation.
   * @param object $context
   *  Context for operations.
   */
  public function unpublishedThirtyNotification($id, $nid, $operation_details, &$context) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $message_service = \Drupal::service('ldbase_handlers.message_service');
    $node = $node_storage->load($nid);
    // send messages
    $message_service->contentUnpublishedReminder($node);

    $context['results'][] = $id;
    // Optional message displayed under the progressbar.
    $context['message'] = t('Running Batch "@id" @details',
      ['@id' => $id, '@details' => $operation_details]
    );
  }

   /**
   * Batch Finished Callback.
   *
   * @param bool $success
   *  Success of the operation.
   * @param array $results
   *  Array of results for post processing.
   * @param array $operations
   *  Array of operations
   */
  public function notificationsFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $messenger->addMessage(t('@count messages sent.', ['@count' => count($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }
}
