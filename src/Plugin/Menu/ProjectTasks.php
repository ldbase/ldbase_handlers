<?php

namespace Drupal\ldbase_handlers\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Cache\Cache;

/**
 * menulink test
 */
class ProjectTasks extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      return ['node' => $node->id()];
    }
    else {
      return ['node' => 'not_a_node'];
    }

  }
}
