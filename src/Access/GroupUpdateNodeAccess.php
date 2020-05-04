<?php

namespace Drupal\ldbase_handlers\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\Routing\Route;

/**
 * Access handler checks ldbase webform routes to group content
 */

 class GroupUpdateNodeAccess implements AccessInterface {

  protected $entityTypeManager;

  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function access(AccountInterface $account, Node $node) {
    $group_contents = GroupContent::loadByEntity($node);
    $node_group = array_pop($group_contents)->getGroup();
    $node_type = $node->getType();
    return $node_group->hasPermission('update any group_node:' . $node_type . ' content', $account)
      ? AccessResult::allowed() : AccessResult::forbidden();
  }

 }
