<?php

namespace Drupal\ldbase_handlers\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts routing parameter uuid into node
 */
class UuidConverterService implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, $defaults) {
    $node_loaded_by_uuid = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $value]);
    $node_loaded_by_uuid = reset($node_loaded_by_uuid);
    return $node_loaded_by_uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (bool) !empty($definition['type']) && $definition['type'] == 'ldbase_uuid';
  }

}
