<?php
namespace Drupal\ldbase_handlers;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

/**
 * Class LDbaseUnpublishService.
 */

 class LDbaseUnpublishService implements LDbaseUnpublishServiceInterface {

    /**
     * An entity type manager interface.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityManager;

  /**
   * Constructs a new LDbaseUnpublishService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   An entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Unpublish child nodes
   *
   * @param int $nid
   *  parent nid
   *
   * @return int[]
   *  array of node ids that were unpublished
   *
   */
  public function unpublishChildNodes($nid) {
    $node_storage = $this->entityManager->getStorage('node');
    $nids_to_uunpublish = $this->getChildNids($nid);
    $unpublished_nodes = [];
    foreach ($nids_to_uunpublish as $nid) {
      $node = $node_storage->load($nid);
      if ($node->status->value == 1) {
        $node->set('status', 0);
        $node->save();
        array_push($unpublished_nodes, $nid);
      }
    }
    return $unpublished_nodes;
  }

  /**
   * get children nids
   *
   * @param int $nid
   *  Gets child nids of given nid.
   *
   * @return int[]
   *  array of node ids
   */
  public function getChildNids($nid) {
    $nid_array = [];
    $node_storage = $this->entityManager->getStorage('node');
    $children_ids = $node_storage->getQuery()
      ->condition('field_affiliated_parents', $nid)
      ->execute();

    foreach ($children_ids as $child) {
      array_push($nid_array, $child);
      $nid_array = array_merge($nid_array, $this->getChildNids($child));
    }
    return $nid_array;
  }

  /**
   * Checks if given nid has unpublished child.
   *
   * @param int $nid
   * Gets child nids of given nid.
   *
   * @return
   * True if nid has unpublished child, otherwise false
   *
   */
  public function hasUnpublishedChild($nid) {
    $has_unpublished_child = false;
    $node_storage = $this->entityManager->getStorage('node');
    $children_ids = $this->getChildNids($nid);

    foreach ($children_ids as $child) {
      $node = $node_storage->load($child);
      $status = $node->status->value;
      if ($status == 0) {
        $has_unpublished_child = true;
        break;
      }
    }
    return $has_unpublished_child;
  }

 }
