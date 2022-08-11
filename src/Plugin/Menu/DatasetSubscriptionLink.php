<?php

namespace Drupal\ldbase_handlers\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates link to subscribe to Dataset updates
 */
class DatasetSubscriptionLink extends MenuLinkDefault {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new Subscribe to Datasets link.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $route_match, StaticMenuLinkOverridesInterface $static_override, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
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
      $container->get('menu_link.static.overrides'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    if ($node = $this->getNodeParameter()) {
      $uuid = $node->uuid();
      $user_id = $this->currentUser->id();

      $subscribed = $this->getSubscriptionStatus($node, $user_id);
      $change = $subscribed ? 'stop' : 'start';
    }
    else {
      // if no nid, pass some text to keep Structure > Menus > Edit Menu from breaking
      $uuid = 'not_a_node';
      $user_id = $this->currentUser->id();
      $change = 'start';
    }
    return ['node' => $uuid, 'user' => $user_id, 'change' => $change];
  }

  /**
   * Checks whether user has subscription for this node
   *
   * @param $node
   * @param $uid
   * @return bool
   */
  private function getSubscriptionStatus($node, $uid) {
    $subscribed = false;
    // check if user has already subscribed
    if ($node->bundle() == 'dataset') {
      $field_subscribed_users = $node->field_subscribed_users->getValue();
      foreach ($field_subscribed_users as $value) {
        if ($value['target_id'] == $uid) {
          $subscribed = true;
        }
      }
    }
    return $subscribed;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $node = $this->getNodeParameter();
    if (!empty($node)) {
      if ($node->bundle() != 'dataset') {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
    else {
      return false;
    }
  }

  /**
   * Gets the node from the route parameters
   *
   * @return Node
   */
  private function getNodeParameter() {
    return $this->routeMatch->getParameter('node');
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'ldbase_handlers.change_dataset_subscription';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if ($node = $this->getNodeParameter()) {
      $user_id = $this->currentUser->id();
      $subscribed = $this->getSubscriptionStatus($node, $user_id);
      $text = $subscribed ? 'Stop Updates' : 'Get Updates';

      return $text;
    }
    else {
      return 'Dataset Subscription Link';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = parent::getOptions();
    if ($node = $this->getNodeParameter()) {
      $uuid = $node->uuid();
      $user_id = $this->currentUser->id();
      $subscribed = $this->getSubscriptionStatus($node, $user_id);
      $change = $subscribed ? 'stop' : 'start';

      $options['attributes']['class'] = $change . '-subscription-icon';
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
