<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "content_edit_link",
 *   admin_label = @Translation("LDbase Content Edit Link"),
 *   category = @Translation("LDbase Menu"),
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
      if ($node_type == 'document') {
        $document_type = $node->get('field_document_type')->target_id;
        $document_type_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($document_type)->getName();
        if ($document_type_term == 'Codebook') {
          $node_type = 'codebook';
        }
      }

      $route = 'ldbase_handlers.edit_' . $node_type;
      $text = 'Edit';
      $class[] = 'ldbase-button';

      $url = Url::fromRoute($route, array('node' => $nid));
      $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
      $link['#attributes'] = ['class' => $class];
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
