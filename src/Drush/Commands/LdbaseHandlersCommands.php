<?php

namespace Drupal\ldbase_handlers\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class LdbaseHandlersCommands extends DrushCommands {

  /**
   * Constructs a LdbaseHandlersCommands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Get nodes that have remained unpublished for 30 days
   */
  #[CLI\Command(name: 'ldbase_handlers:unpublished-thirty-days', aliases: ['ldbase:utd'])]
  #[CLI\Usage(name: 'ldbase_handlers:unpublished-thirty-days', description: 'Straight-forward Usage without options or args')]
  public function unpublishedThirtyDays() {
    // log process start
    $this->logger()->success(dt('Beginning process ...'));
    $this->loggerFactory->get('ldbase')->info('Searching for nodes unpublished for 30 days ...');

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
      $this->loggerFactory->get('ldbase')->warning('No eligible unpublished nodes found.');
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
    $this->logger->info($message);
    $this->loggerFactory->get('ldbase')->info($message);
  }

}
