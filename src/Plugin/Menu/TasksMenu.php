<?php

namespace Drupal\ldbase_handlers\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Cache\Cache;

/**
 * Gets the nid for the currently viewed node and assigns it to a
 * route parameter.
 */
class TasksMenu extends MenuLinkDefault {

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
      // if no nid, pass some text to keep Structure > Menus > Edit Menu from breaking
      return ['node' => 'not_a_dataset'];
    }

  }
}
