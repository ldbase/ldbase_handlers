<?php

namespace Drupal\ldbase_handlers\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user_email_verification\UserEmailVerification;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Contact link for Projects and Persons.
 *
 * @Block(
 *   id = "ldbase_contact_link",
 *   admin_label = @Translation("LDbase Contact Link"),
 *   category = @Translation("LDbase Block"),
 *   context = {
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The User Email Verification service.
   *
   * @var \Drupal\user_email_verification\UserEmailVerification;
   */
  protected $userEmailVerification;

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
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The Current User
   * @param \Drupal\user_email_verification\UserEmailVerification $userEmailVerification
   *   User Email Verification Service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, UserEmailVerification $userEmailVerification) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->userEmailVerification = $userEmailVerification;
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
      $container->get('current_user'),
      $container->get('user_email_verification.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');
    $node_type = $node->getType();
    $uuid = $node->uuid();
    $allowed_types = ['person','project'];
    $markup = '';
    $show_link = false;
    $uid = $this->currentUser->id();

    if (!empty($uuid) && in_array($node_type, $allowed_types)) {
      $show_link = true;
      // check person has account
      if ($node_type == 'person') {
        if (empty($node->field_drupal_account_id->target_id)) {
          $show_link = false;
        }
      }
      // check if user has verified email
      if ($this->userEmailVerification->isVerificationNeeded($uid)) {
        $show_link = false;
      }

      if ($show_link) {
        $route = 'ldbase_handlers.contact_' . $node_type;
        $text = 'Contact ' . ucfirst($node_type);

        $url = Url::fromRoute($route, array('node' => $uuid));
        if ($url->access()) {
          $link = Link::fromTextAndUrl(t($text), $url)->toRenderable();
          $markup .= render($link) . ' ';
        }
      }
      else {
        $markup = '';
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
