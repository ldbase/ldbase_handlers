<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "download_all_files_link",
 *   admin_label = @Translation("LDbase Download All Files Link"),
 *   category = @Translation("LDbase Menu"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class DownloadAllFilesLink extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Construct a new Contact Link Block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $uuid = $node->uuid();
    $markup = '';
    $show_button = false;
    if (!empty($uuid)) {
      $child_nodes_with_files = $this->getFileChildNodes($node);
      $account = \Drupal::currentUser();
      $embargo_service = \Drupal::service('ldbase_embargoes.file_access');
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
            $embargo = $embargo_service->isActivelyEmbargoed($file_entity, $account);
            if (!$embargo->isForbidden()) {
              $show_button = true;
            }
          }
        }
        if ($show_button) {
          $route = 'ldbase_handlers.download_all_project_files';
          $text = 'DOWNLOAD all Project Files';
          $class[] = 'download-all-files-button';

          $url = Url::fromRoute($route, array('node' => $uuid));
          if ($url->access()) {
            $markup = '<span class="download-all-files download-all-files-icon">';
            $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
            $link['#attributes'] = ['class' => $class];
            $markup .= $this->renderer->render($link) . ' ';
            $markup .= '</span>';
          }
        }
      }
    }

    $block = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    return $block;
  }


  /**
   * get children nids
   *
   * @param \Drupal\Node\NodeInterface $node
   *  Gets child nodes of given node.
   *
   * @return array
   *  array of nodes
   */
  private function getFileChildNodes(NodeInterface $node) {
    $bundles_with_files = ['dataset','code','document'];
    $node_array = [];
    $nid = $node->id();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $children_ids = $node_storage->getQuery()
      ->accessCheck(TRUE)
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

}
