<?php

namespace Drupal\ldbase_handlers;

interface LDbaseUnpublishServiceInterface {

  /**
   * Unpublish child nodes
   *
   */
  public function unpublishChildNodes($nid);

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

}
