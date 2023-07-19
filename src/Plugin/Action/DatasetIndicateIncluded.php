<?php

namespace Drupal\ldbase_handlers\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Indicate that dataset is included in the Integrated Dataset
 *
 * @Action(
 *   id = "dataset_indicate_included",
 *   label = @Translation("Indicate Included in Integrated Dataset."),
 *   type = "node"
 * )
 */
class DatasetIndicateIncluded extends ActionBase {

  /**
   * @inheritDoc
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // TODO: Implement access() method.
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function execute($node = NULL) {
    /** @var \Drupal\node\NodeInterface $node */
    // TODO: Implement execute() method.
    if ($node && $node->hasField('field_added_to_integrated_data')) {
      $node->set('field_added_to_integrated_data',1);
      $node->save();
    }
  }

}
