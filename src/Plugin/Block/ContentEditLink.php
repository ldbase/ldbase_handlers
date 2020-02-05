<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "content_edit_link",
 *   admin_label = @Translation("LDBase Content Edit Link"),
 *   category = @Translation("LDBase Menu"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class ContentEditLink extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $node_type = $node->getType();
    $nid = $node->id();

    $markup = '';
    if (!empty($nid)) {
      $route = 'ldbase_handlers.edit_' . $node_type;
      $text = 'Edit';

      $url = Url::fromRoute($route, array('node' => $nid));
      $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
      $markup .= render($link) . ' ';
    } else {
      $markup = "Link Requires Node Id.";
    }

    $block = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    return $block;
  }
}
