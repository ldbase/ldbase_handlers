<?php

namespace Drupal\ldbase_handlers\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Cache\Cache;
use Drupal\group\Entity\GroupRelationship;

/**
 * Gets the nid for the currently viewed node and assigns it to a
 * route parameter.
 */
class TasksMenuWithGroup extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('user'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
        $group_contents = GroupRelationship::loadByEntity($node);
        $group = array_pop($group_contents)->getGroup();

      return ['node' => $node->uuid(), 'group' => $group->id()];
    }
    else {
      // if no nid, pass some text to keep Structure > Menus > Edit Menu from breaking
      return ['node' => 'not_a_node', 'group' => 'not_a_group'];
    }

  }

  public function getCacheMaxAge() {
    return 0;
  }
}
