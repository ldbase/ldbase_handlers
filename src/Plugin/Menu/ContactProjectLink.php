<?php

namespace Drupal\ldbase_handlers\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates link to contact project members
 */
class ContactProjectLink extends MenuLinkDefault {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Constructs a new Contact Project Members link.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $route_match, StaticMenuLinkOverridesInterface $static_override) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('menu_link.static.overrides')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    if ($node = $this->routeMatch->getParameter('node')) {
      $uuid = $node->uuid();
    }
    else {
      // if no nid, pass some text to keep Structure > Menus > Edit Menu from breaking
      $uuid = 'not_a_node';
    }
    return ['node' => $uuid];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $node = $this->routeMatch->getParameter('node');
    if (!empty($node)) {
      if ($node->bundle() != 'project') {
        return FALSE;
      }
      else {
        /* if this is a project node show link*/
        $show_link = TRUE;
        /* unless project doesn't want you to */
        if ($node->field_do_not_contact->value) {
          $show_link = FALSE;
        }
        return $show_link;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'ldbase_handlers.contact_project';
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = parent::getOptions();
    if ($node = $this->routeMatch->getParameter('node')) {
      $options['attributes']['class'] = 'ldbase-contact-link-icon';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('user'));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }
}
