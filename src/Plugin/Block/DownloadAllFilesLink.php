<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Node\NodeInterface;

/**
 * @Block(
 *   id = "download_all_files_link",
 *   admin_label = @Translation("LDbase Download All Files Link"),
 *   category = @Translation("LDbase Menu"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class DownloadAllFilesLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $uuid = $node->uuid();
    $markup = '';

    if (!empty($uuid)) {
      $child_nodes_with_files = $this->getFileChildNodes($node);
      if (!empty($child_nodes_with_files)) {
        $route = 'ldbase_handlers.download_all_project_files';
        $text = 'Download all Files';
        $class[] = 'button';

        $url = Url::fromRoute($route, array('node' => $uuid));
        if ($url->access()) {
          $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
          $link['#attributes'] = ['class' => $class];
          $markup .= render($link) . ' ';
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
