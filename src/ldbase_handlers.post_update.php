<?php

/** Add Integrated Dataset Actions */

/**
 * Implements hook_post_update_NAME().
 */
function ldbase_handlers_post_update_add_integrated_dataset_actions(&$sandbox) {
  $actions = [
    [
      'id' => 'dataset_indicate_included',
      'label' => 'Included in Integrated Dataset',
      'type' => 'node',
      'plugin' => 'dataset_indicate_included',
    ],
    [
      'id' => 'dataset_negate_included',
      'label' => 'Not Included in Integrated Dataset',
      'type' => 'node',
      'plugin' => 'dataset_negate_included',
    ],
   ];
  $entity_type_manager = \Drupal::entityTypeManager();

  foreach ($actions as $action) {
    $entity_type_manager->getStorage('action')->create($action)->save();
  }
}
