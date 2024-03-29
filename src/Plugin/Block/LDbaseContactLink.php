<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Contact link for Projects and Persons.
 *
 * @Block(
 *   id = "ldbase_contact_link",
 *   admin_label = @Translation("LDbase Contact Link"),
 *   category = @Translation("LDbase Block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node")
 *     )
 *   }
 * )
 */

 class LDbaseContactLink extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   * @throws \Exception
   */
  public function build() {
    $node = $this->getContextValue('node');
    $node_type = $node->getType();
    $uuid = $node->uuid();
    $allowed_types = ['person','project'];
    $markup = '';
    $show_link = false;

    if (!empty($uuid) && in_array($node_type, $allowed_types)) {
      $show_link = true;
      // check if target person has account
      if ($node_type == 'person') {
        $text = 'Contact this person';
        if (empty($node->field_drupal_account_id->target_id)) {
          $show_link = false;
        }
      }
      else {
        $text = 'Contact members of this project';
      }

      if ($node->field_do_not_contact->value) {
        $show_link = false;
      }

      if ($show_link) {
        $route = 'ldbase_handlers.contact_' . $node_type;


        $url = Url::fromRoute($route, array('node' => $uuid));
        if ($url->access()) {
          $markup = '<div class="ldbase-contact-link-icon">';
          $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
          $class[] = 'ldbase-contact-link';
          $link['#attributes'] = ['class' => $class];
          $markup .= $this->renderer->render($link) . ' ';
          $markup .= '</div>';
        }
      }
      else {
        $markup = '';
      }
    }
    else {
      if ($node_type == 'tombstone') {
        $markup = "";
      }
      else {
        $markup = "Link Requires Node Id.";
      }
    }

    $block = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    return $block;
  }

 }
