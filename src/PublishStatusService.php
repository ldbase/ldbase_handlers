<?php
namespace Drupal\ldbase_handlers;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

/**
 * Class PublishStatusService.
 */

 class PublishStatusService implements PublishStatusServiceInterface {

  const UNPUBLISHED_PATTERN = "/\(unpublished\)$/";
  const UNPUBLISHED_TEXT = " (unpublished)";

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
    $nids_to_change = $this->getChildNids($nid);
    $unpublished_nodes = $this->changePublishStatus($nids_to_change, false);
    return $unpublished_nodes;
  }

  /**
   * Publish this and all children
   *
   * @param int $nid
   *  parent nid
   *
   * @return int[]
   *  array of node ids that were unpublished
   */
  public function publishNodeAndChildren($nid) {
    $nids_to_change = $this->getChildNids($nid);
    $nids_to_change[] = $nid;
    $published_nodes = $this->changePublishStatus($nids_to_change, true);
    return $published_nodes;
  }

  /**
   * Publish child nodes
   *
   * @param int $nid
   *  parent nid
   *
   * @return int[]
   *  array of node ids that were unpublished
   *
   */
  public function publishChildNodes($nid) {
    $nids_to_change = $this->getChildNids($nid);
    $published_nodes = $this->changePublishStatus($nids_to_change, true);
    return $published_nodes;
  }

  /**
   * Changes publish status of node and children
   *
   * @param int[] $nids_to_change
   *
   * @param bool $publish
   *  publish status to which items are being changed
   *
   * @return int[]
   *  array of node ids that were changed
   *
   */
  private function changePublishStatus($nids_to_change, $publish) {
    $node_storage = $this->entityManager->getStorage('node');
    $changed_nids = [];
    foreach ($nids_to_change as $nid) {
      $node = $node_storage->load($nid);
      $title = $node->getTitle();
      if (!$publish) {
        // add (Unpublished) to title if not there
        if (preg_match(self::UNPUBLISHED_PATTERN, trim($title)) === 0) {
          $title .= self::UNPUBLISHED_TEXT;
          $node->set('title', $title);
        }
      }
      else {
        $published_title = preg_replace(self::UNPUBLISHED_PATTERN, '', trim($title));
        $node->set('title', $published_title);
      }
      if ($node->status->value != $publish) {
        array_push($changed_nids, $nid);
      }
      $node->set('status', $publish);

      $node->save();
    }

    return $changed_nids;
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
      ->accessCheck(TRUE)
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
