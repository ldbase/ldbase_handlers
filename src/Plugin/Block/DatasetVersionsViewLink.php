<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "dataset_versions_view_link",
 *   admin_label = @Translation("LDbase Dataset Versions View Link"),
 *   category = @Translation("LDbase Menu"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Dataset Node")
 *     )
 *   }
 * )
 */
class DatasetVersionsViewLink extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Construct a new Dataset Versions View  Link Block.
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
    $nid = $node->id();
    $uuid = $node->uuid();

    $markup = '';
    if (!empty($nid)) {
      // link if uploaded dataset and more than 1 version exists
      if ($node->get('field_dataset_upload_or_external')->value === 'upload' && count($node->field_dataset_version) > 1) {
        $route = 'view.dataset_versions.all_versions';
        $text = 'View All Dataset Versions';
        $class[] = 'datasets-view-button';

        $url = Url::fromRoute($route, array('node' => $nid, 'uuid' => $uuid));

        if (!($url->access())) {
          $route = 'view.dataset_versions.view_only';
          $url = Url::fromRoute($route, array('node' => $nid, 'uuid' => $uuid));
        }
        $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
        $link['#attributes'] = ['class' => $class];
        $markup .= $this->renderer->render($link) . ' ';
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
