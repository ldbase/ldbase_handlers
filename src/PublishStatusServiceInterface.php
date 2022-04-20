<?php

namespace Drupal\ldbase_handlers;

interface PublishStatusServiceInterface {

  /**
   * Unpublish child nodes
   *
   */
  public function unpublishChildNodes($nid);

  /**
   * Publish this and all children
   *
   * @param int $nid
   *  parent nid
   *
   * @return int[]
   *  array of node ids that were unpublished
   */
  public function publishNodeAndChildren($nid);

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
  public function publishChildNodes($nid);

  /**
   * get child nodes
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
