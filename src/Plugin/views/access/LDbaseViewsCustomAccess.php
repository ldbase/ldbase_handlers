<?php

namespace Drupal\ldbase_handlers\Plugin\views\access;

use Drupal\Core\Access\AccessResult;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Routing\Route;


/**
 * Class LDbaseViewsCustomAccess
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *  id = "LDbaseViewsCustomAccess",
 *  title = @Translation("Customized LDbase Access for views"),
 *  help = @Translation("Adds Group Update Node Access Check"),
 * )
 */

class LDbaseViewsCustomAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t("LDbase Group Update Node Access");
  }

  public function access(AccountInterface $account) {
    $node = \Drupal::routeMatch()->getParameter('node');
    return \Drupal::service('ldbase_handlers.group_content.update_access_checker')->access($account, $node);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    // add access check and parameters to route
    $route->setRequirement('_group_update_node_access_check', 'TRUE');
    $route->setOption('parameters', array('uuid' => ['type' => 'ldbase_uuid'], 'node' => ['type' => 'entity:node']));
  }

}
