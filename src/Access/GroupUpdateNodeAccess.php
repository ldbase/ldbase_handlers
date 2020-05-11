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
    // get node's group
    $group_contents = GroupContent::loadByEntity($node);
    $node_group = array_pop($group_contents)->getGroup();
    // get user's membership and roles in group
    $group_member = $node_group->getMember($account);
    if ($group_member) {
      $group_member_roles = $group_member->getRoles();
      //dd($group_member_roles);
      // allowed roles
      $allowed_roles = ['project_group-editor','project_group-administrator'];
      $allow_access = FALSE;
      foreach ($allowed_roles as $role) {
        if (array_key_exists($role, $group_member_roles)) {
          $allow_access = TRUE;
        }
      }
      return $allow_access ? AccessResult::allowed() : AccessResult::forbidden();
    }
    else {
      return AccessResult::forbidden();
    }
  }

 }
