<?php
namespace Drupal\ldbase_handlers;

use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * get  nodes
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

 }
