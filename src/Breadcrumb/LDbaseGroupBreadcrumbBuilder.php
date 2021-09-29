<?php

namespace Drupal\ldbase_handlers\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\ldbase_content\LDbaseObjectService;


class LDbaseGroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The LDbase Object service.
   *
   * @var \Drupal\ldbase_content\LDbaseObjectService
   */
  protected $ldbaseObjectService;

  /**
   * Constructs an LDbase Group Breadcrumb builder
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *  The entity type manager
   * @param \Drupal\ldbase_content\LDbaseObjectService
   *  The LDBase Object Service
   *
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LDbaseObjectService $ldbase_object_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->ldbaseObjectService = $ldbase_object_service;
  }

  public function applies(RouteMatchInterface $route_match) {
    $destination_query = \Drupal::request()->query->get('destination');
    if (!empty($destination_query) && $this->ldbaseObjectService->isUrlAnLdbaseObjectUrl($destination_query)) {
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
    $ldbase_object_uuid = $this->ldbaseObjectService->isUrlAnLdbaseObjectUrl($url);
    $ldbase_object = $this->ldbaseObjectService->getLdbaseObjectFromUuid($ldbase_object_uuid);
    $breadcrumb_trail = $this->ldbaseObjectService->getBreadcrumbTrailToLdbaseObject(array(array('title' => $ldbase_object->getTitle(), 'nid' => $ldbase_object->id())));

    $url_bits_count = count(explode('/', $url));
    if ($url_bits_count <= 3) {
      array_pop($breadcrumb_trail);
    }

    foreach ($breadcrumb_trail as $breadcrumb_link) {
      $ntity = $this->entityTypeManager->getStorage('node')->load($breadcrumb_link['nid']);
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
