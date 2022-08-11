<?php

namespace Drupal\ldbase_handlers\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ldbase_embargoes\Access\EmbargoedFileAccess;

/**
 * Creates link to subscribe to Dataset updates
 */
class DownloadAllFilesLink extends MenuLinkDefault {

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
   * the LDbase embargoes file access service
   *
   * @var \Drupal\ldbase_embargoes\Access\EmbargoedFileAccess
   */
  protected $embargoAccess;

  /**
   * An entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

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
   * @param \Drupal\ldbase_embargoes\Access\EmbargoedFileAccess $embargo_access
   *   The LDbase embargo access service
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   An entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $route_match, StaticMenuLinkOverridesInterface $static_override, AccountInterface $current_user, EmbargoedFileAccess $embargo_access, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->embargoAccess = $embargo_access;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('current_user'),
      $container->get('ldbase_embargoes.file_access'),
      $container->get('entity_type.manager')
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
        /* if this is a project node */
        $show_link = FALSE;
        $child_nodes_with_files = $this->getFileChildNodes($node);
        $account = $this->currentUser;

        if (!empty($child_nodes_with_files)) {
        // get files and check for embargoes
          foreach ($child_nodes_with_files as $child_node) {
            $type = $child_node->bundle();
            switch ($type) {
              case 'dataset':
                $versions = [];
                $dataset_versions = $child_node->field_dataset_version;
                foreach ($dataset_versions as $delta => $paragraph) {
                  $p = $paragraph->entity;
                  $versions[$delta] = $p->field_file_upload->entity;
                }
                $latest_version = end($versions);
                $file_entity = $latest_version;
                break;
              case 'code':
                $file_entity = $child_node->field_code_file->entity;
                break;
              case 'document':
                $file_entity = $child_node->field_document_file->entity;
                break;
            }
            if ($file_entity) {
              // check for embargoes
              $embargo = $this->embargoAccess->isActivelyEmbargoed($file_entity, $account);
              if (!$embargo->isForbidden()) {
                $show_link = true;
              }
            }
          }
        }
        return $show_link;
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * get children nids
   *
   * @param  Drupal\node\Entity\Node $node
   *  Gets child nodes of given node.
   *
   * @return array
   *  array of nodes
   */
  private function getFileChildNodes(Node $node) {
    $bundles_with_files = ['dataset','code','document'];
    $node_array = [];
    $nid = $node->id();
    $node_storage = $this->entityTypeManager->getStorage('node');
    $children_ids = $node_storage->getQuery()
      ->condition('field_affiliated_parents', $nid)
      ->execute();

    foreach ($children_ids as $child_id) {
      $child_node = $node_storage->load($child_id);
      $child_node_bundle = $child_node->bundle();
      if (in_array($child_node_bundle, $bundles_with_files)) {
        switch ($child_node_bundle) {
          case 'dataset':
            $upload_or_external = $child_node->get('field_dataset_upload_or_external')->value;
            break;
          case 'code':
            $upload_or_external = $child_node->get('field_code_upload_or_external')->value;
            break;
          case 'document':
            $upload_or_external = $child_node->get('field_doc_upload_or_external')->value;
            break;
        }
        if ($upload_or_external == 'upload') {
          array_push($node_array, $child_node);
        }
      }
      $node_array = array_merge($node_array, $this->getFileChildNodes($child_node));
    }
    return $node_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'ldbase_handlers.download_all_project_files';
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = parent::getOptions();
    if ($node = $this->routeMatch->getParameter('node')) {
      $options['attributes']['class'] = 'download-all-files-icon';
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
