<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "content_delete_link",
 *   admin_label = @Translation("LDbase Delete Content Link"),
 *   category = @Translation("LDbase Menu"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */
class ContentDeleteLink extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

    $node = $this->getContextValue('node');
    $node_type = $node->getType();
    $uuid = $node->uuid();

    $markup = '';
    if (!empty($uuid)) {

      $route = 'ldbase_handlers.confirm_' . $node_type . '_deletion';
      $text = 'Delete ' . ucfirst($node_type) . ' ...';
      $class[] = 'ldbase-button';

      $url = Url::fromRoute($route, array('node' => $uuid));
      if ($url->access()) {
        $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
      $link['#attributes'] = ['class' => $class];
      $markup .= render($link) . ' ';
      }

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
