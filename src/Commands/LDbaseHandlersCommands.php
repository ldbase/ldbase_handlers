<?php
namespace Drupal\ldbase_handlers\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class LDbaseHandlersCommands extends DrushCommands {
  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;
  /**
   * Constructs a new LDbaseNewAccountCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }

  /**
   * Get nodes that have remained unpublished for 30 days
   *
   * @command ldbase:unpublished-thirty-days
   * @aliases ldbase:utd
   */
  public function unpublishedThirtyDays() {
    // log process start
    $this->loggerChannelFactory->get('ldbase')->info('Searching for nodes unpublished for 30 days ...');

    // get person nodes that are not connected to a drupal user
    $node_storage = $this->entityTypeManager->getStorage('node');
    $unpublished_ids = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('changed', strtotime('-30 days'), '<=')
      ->condition('status', 0)
      ->execute();

    // create operations array for the batch
    $operations = [];
    $numOperations = 0;
    $batchId = 1;
    if (!empty($unpublished_ids)) {
      foreach($unpublished_ids as $nid) {
        $this->output()->writeln("Preparing batch: " . $batchId);
        $operations[] = [
          '\Drupal\ldbase_handlers\HandlersBatchService::unpublishedThirtyNotification',
          [
            $batchId,
            $nid,
            t('Unpublished node @nid', ['@nid' => $nid]),
          ]
        ];
        $batchId++;
        $numOperations++;
      }
    }
    else {
      $this->logger()->warning('No eligible unpublished nodes found.');
    }

    // create batch
    $batch = [
      'title' => t('Queuing @num notification(s)', ['@num' => $numOperations]),
      'operations' => $operations,
      'finished' => '\Drupal\ldbase_handlers\HandlersBatchService::notificationsFinished',
    ];

    // add batch operations as new batch sets
    batch_set($batch);

    // process batch sets
    drush_backend_batch_process();

    // Show some information.
    $this->logger()->notice("Batch operations end.");
    // Log some information.
    $message = 'Finished queuing ' . $numOperations . ' notificiation(s) of long-unpublished nodes.';
    $this->loggerChannelFactory->get('ldbase')->info($message);

  }
}
