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
    $uuid = $node->uuid();

    $markup = '';
    if (!empty($nid)) {
      // link if uploaded dataset and more than 1 version exists
      if ($node->get('field_dataset_upload_or_external')->value === 'upload' && count($node->field_dataset_version) > 1) {
        $route = 'view.dataset_versions.all_versions';
        $text = 'View All Dataset Versions';
        $class[] = 'ldbase-button datasets-view-button';

        $url = Url::fromRoute($route, array('node' => $nid, 'uuid' => $uuid));

        if (!($url->access())) {
          $route = 'view.dataset_versions.view_only';
          $url = Url::fromRoute($route, array('node' => $nid, 'uuid' => $uuid));
        }
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
