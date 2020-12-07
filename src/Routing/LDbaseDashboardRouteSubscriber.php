<?php

namespace Drupal\ldbase_handlers\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class LdbaseDashboardRouteSubscriber extends RouteSubscriberBase {
  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // change title callback of /user to title 'My Dashboard'
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setDefault('_title', 'My Dashboard');
      $route->setDefault('_title_callback', '');
    }
  }
}
