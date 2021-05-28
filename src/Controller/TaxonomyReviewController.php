<?php
namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxonomyReviewController extends ControllerBase {

  /**
   * An entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TaxonomyReview contructor
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function reviewPage() {
    $page_text = "Any terms added by users will appear here grouped by taxonomy.";
    return [
      '#markup' => '<p>' . t($page_text) . '</p>',
    ];
  }

}
