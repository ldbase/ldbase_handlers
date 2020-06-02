<?php

namespace Drupal\ldbase_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a "Request File Access" block.
 *
 * @Block(
 *   id="request_file_access_button",
 *   admin_label = @Translation("Request File Access Button"),
 *   category = @Translation("LDbase Block")
 * )
 */
class LDbaseRequestFileAccessButton extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $text = $this->t('Request file access');
    $url = '/form/request-file-access/';

    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $url .= $node->id();
    }

    return [
      '#markup' => "<div><a class='button button-action' href='{$url}'>{$text}</a></div>",
    ];
  }
}
