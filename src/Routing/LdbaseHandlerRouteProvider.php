<?php

namespace Drupal\ldbase_handlers\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes to delete LDbase content
 */
class LdbaseHandlerRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $route_collection = new RouteCollection();
    // create routes for these content types
    $ldbase_bundles = array('project', 'dataset', 'code', 'document');
    foreach ($ldbase_bundles as $ldbase_bundle) {
      $pluralizer = ($ldbase_bundle == 'code') ? '' : 's';
      // create routes for each content type, going to same delete form
      $route = new Route(
        // path:
        '/' . $ldbase_bundle . $pluralizer. '/{node}/delete-content',
        // Route defaults:
        [
          '_form' => '\Drupal\ldbase_handlers\Form\ConfirmEntityAndChildrenDeleteForm',
          '_title' => 'Confirm Content Deletion'
        ],
        // Requirements:
        [
          '_group_delete_node_access_check' => 'TRUE',
        ],
        // Options:
        [
          'parameters' => [
            'node' => [
              'type' => 'ldbase_uuid'
            ]
          ]
        ]
      );

      // Add the route to the collection
      $route_collection->add("ldbase_handlers.confirm_{$ldbase_bundle}_deletion", $route);
    }

    return $route_collection;
  }

}
