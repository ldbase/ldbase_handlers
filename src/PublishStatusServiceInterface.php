<?php

namespace Drupal\ldbase_handlers;

interface PublishStatusServiceInterface {

  /**
   * Unpublish child nodes
   *
   */
  public function unpublishChildNodes($nid);

  /**
   * Unpublish child nodes
   *
   */
  public function publishChildNodes($nid);

  /**
   * get  nodes
   *
   * @param int $nid
   *  Gets child nids of given nid.
   *
   * @return int[]
   *  array of node ids
   */
  public function getChildNids($nid);

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
  public function hasUnpublishedChild($nid);

}
