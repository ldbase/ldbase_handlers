<?php

namespace Drupal\ldbase_handlers\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;


class LDbaseGroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  public function applies(RouteMatchInterface $route_match) {
    $destination_query = \Drupal::request()->query->get('destination');
    if (!empty($destination_query) && \Drupal::service('ldbase.object_service')->isUrlAnLdbaseObjectUrl($destination_query)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function build(RouteMatchInterface $route_match) {

    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path', 'route']);
    $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));
    // prefix 'Projects' view link
    $breadcrumb->addLink(Link::createFromRoute('Projects', 'view.projects.page_1'));
    // use the query string destination value
    $url = \Drupal::request()->query->get('destination');
    $ldbase_object_uuid = \Drupal::service('ldbase.object_service')->isUrlAnLdbaseObjectUrl($url);
    $ldbase_object = \Drupal::service('ldbase.object_service')->getLdbaseObjectFromUuid($ldbase_object_uuid);
    $breadcrumb_trail = \Drupal::service('ldbase.object_service')->getBreadcrumbTrailToLdbaseObject(array(array('title' => $ldbase_object->getTitle(), 'nid' => $ldbase_object->id())));

    $url_bits_count = count(explode('/', $url));
    if ($url_bits_count <= 3) {
      array_pop($breadcrumb_trail);
    }

    foreach ($breadcrumb_trail as $breadcrumb_link) {
      $entity = entity_load('node', $breadcrumb_link['nid']);
      $formatted_bundle = ucfirst($entity->bundle());
      $formatted_title = "{$formatted_bundle}: {$breadcrumb_link['title']}";
      $breadcrumb->addLink(Link::createFromRoute($formatted_title, 'entity.node.canonical', ['node' => $breadcrumb_link['nid']], ['absolute' => TRUE]));
    }

    // add link back to Members view
    $url_bits = explode('/', $url);
    $group_bit = $url_bits[3];
    $breadcrumb->addLink(Link::createFromRoute('Members', 'view.group_members.ldbase_project', ['node' => $ldbase_object_uuid, 'group' => $group_bit]));

    return $breadcrumb;
  }
}
