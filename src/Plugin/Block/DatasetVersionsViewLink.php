<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "dataset_versions_view_link",
 *   admin_label = @Translation("LDbase Dataset Versions View Link"),
 *   category = @Translation("LDbase Menu"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Dataset Node")
 *     )
 *   }
 * )
 */
class DatasetVersionsViewLink extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

    $node = $this->getContextValue('node');
    $nid = $node->id();

    $markup = '';
    if (!empty($nid)) {
      $route = 'view.dataset_versions.all_versions';
      $text = 'View All Dataset Versions';
      $class[] = 'ldbase-button datasets-view-button';

      $url = Url::fromRoute($route, array('arg_0' => $nid));
      if ($url->access()) {
        $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
        $link['#attributes'] = ['class' => $class];
        $markup .= render($link) . ' ';
      }
    }
    else {
      $markup = "Link Requires Node Id.";
    }

    $block = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    return $block;
  }

}
