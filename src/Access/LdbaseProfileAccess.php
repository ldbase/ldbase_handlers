<?php

namespace Drupal\ldbase_handlers\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Routing\Route;

/**
 * Access handler checks ldbase webform routes to group content
 */

class LdbaseProfileAccess implements AccessInterface {

  protected $entityTypeManager;

  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public function access(AccountInterface $account, \Drupal\Node\NodeInterface $node = NULL) {
    if ($node) {
      $profile_id = $node->get('field_drupal_account_id')->target_id;
      $user_id = \Drupal::currentUser()->id();
      if ($node->getType() === 'person' && $profile_id === $user_id) {
        return AccessResult::allowed()->addCacheableDependency($node)->cachePerUser();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    else {
      return AccessResult::neutral();
    }

  }

}
