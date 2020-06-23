<?php

namespace Drupal\ldbase_handlers\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts routing parameter uuid into node
 */
class ParagraphIdConverterService implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, $defaults) {
    $paragraph_loaded = Paragraph::load($value);
    return $paragraph_loaded;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (bool) !empty($definition['type']) && $definition['type'] == 'ldbase_paragraph';
  }

}
