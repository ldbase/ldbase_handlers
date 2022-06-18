<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @Block(
 *   id = "dataset_subscription_link",
 *   admin_label = @Translation("LDbase Dataset Subscription Link"),
 *   category = @Translation("LDbase Block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */

 class DatasetSubscriptionLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $markup = '';

    if (!empty($node)) {
      if ($node->bundle() == 'dataset' && $node->field_dataset_upload_or_external->value == 'upload') {
        $uuid = $node->uuid();
        $user_id = \Drupal::currentUser()->id();
        $subscribed = false;
        // check if user has already subscribed
        $field_subscribed_users = $node->field_subscribed_users->getValue();
        foreach ($field_subscribed_users as $value) {
          if ($value['target_id'] == $user_id) {
            $subscribed = true;
          }
        }
        $route = 'ldbase_handlers.change_dataset_subscription';
        $text = $subscribed ? 'Stop Updates' : 'Get Updates';
        $change = $subscribed ? 'stop' : 'start';
        $class = ['dataset-subscription-button', $change.'-subscription'];

        $url = Url::fromRoute($route, array('node' => $uuid, 'user' => $user_id, 'change' => $change));
        if ($url->access()) {
          $markup = '<span class="subscription-icon ' . $change . '-subscription-icon">';
          $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
          $link['#attributes'] = ['class' => $class];
          $markup .= render($link) . ' ';
          $markup .= '</span>';
        }
      }
    }

    $block = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    return $block;
  }

 }
