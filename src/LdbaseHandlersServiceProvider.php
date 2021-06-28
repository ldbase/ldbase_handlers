<?php

namespace Drupal\ldbase_handlers;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the service
 */
class LdbaseHandlersServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('session_configuration');
    $definition->setClass('Drupal\ldbase_handlers\LdbaseHandlersSessionConfiguration');
  }

}
