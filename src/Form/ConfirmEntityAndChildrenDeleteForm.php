<?php

namespace Drupal\ldbase_handlers\Form;

use Drupal\Node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ldbase_content\LDbaseObjectService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines form to confirm deletion of LDbase content and its children.
 */
class ConfirmEntityAndChildrenDeleteForm extends ConfirmFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The LDbase Object Service
   *
   * @var \Drupal\ldbase_content\LDbaseObjectService
   */
  protected $ldbaseObjectService;

  /**
   * UUID of parent node
   * @param Drupal\Node\NodeInterface $node
   */
  protected $node;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, LDbaseObjectService $ldbase_object_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->ldbaseObjectService = $ldbase_object_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ldbase.object_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->node = $node;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'confirm_delete_content_and_children_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $node = $this->node;
    $route = 'entity.node.canonical';
    $url_parameters = [
      'node' => $node->id(),
    ];
    $url = Url::fromRoute($route, $url_parameters);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $node = $this->node;
    $bundle = $this->ldbaseObjectService->isLdbaseCodebook($node->uuid()) ? 'codebook' : $node->bundle();
    return t('Are you sure you want to delete %type: %title and any related items from LDbase?', [
      '%type' => ucfirst($bundle),
      '%title' => $node->getTitle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $node = $this->node;
    $bundle = $this->ldbaseObjectService->isLdbaseCodebook($node->uuid()) ? 'codebook' : $node->bundle();
    $description = "<div class='delete-content-confirmation'>" .
      '<p>' . t('Related items branch down from %type: %title in the Project Tree.', [
        '%type' => ucfirst($bundle),
        '%title' => $node->getTitle(),
      ]) . '</p>' .
      '<p>' . t('If you confirm, then the following content will be deleted:') . '</p>' .
      "<ul class='delete-content-confirmation-list'>" .
      "<li class='delete-content-confirmation-list-item'>" .
      t(ucfirst($bundle) . ': ' . $node->getTitle()) .
      '</li>' .
      $this->getChildrenAsHtmlList($node->id()) .
      '</ul>' .
      '<p>' . t('This action cannot be undone.') . '</p>' .
      '</div>';
    return Markup::create($description);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this_nid = $this->node->id();
    // redirect afterward to object parent
    // if no parent (project deleted), redirect to account home page
    if ($this->node->getType() !== 'project') {
      $parent_nid = $this->ldbaseObjectService->getLdbaseObjectParent($this_nid);
      $route = 'entity.node.canonical';
      $url_parameters = [
        'node' => $parent_nid,
      ];
      $url = Url::fromRoute($route, $url_parameters);
    }
    else {
      $route = 'entity.user.canonical';
      $url_parameters = [
        'user' => \Drupal::currentUser()->id()
      ];
      $url = Url::fromRoute($route, $url_parameters);
    }
    // add any child nodes to be deleted
    $nodes_to_delete = array_merge([$this_nid], $this->getChildrenIdsAsArray($this_nid));
    foreach ($nodes_to_delete as $id) {
      $delete = $this->entityTypeManager->getStorage('node')->load($id);
      if ($delete) {
        $delete->delete();
      }
    }
    $this->messenger()->addStatus($this->t('The content has been successfully deleted.'));
    $form_state->setRedirectUrl($url);
  }

  /**
   * Return HTML list of nodes descending from affliated parent nodes
   */
  private function getChildrenAsHtmlList($parent_id) {
    $list = '';
    $ordered_types = ['dataset','code','document'];
    foreach ($ordered_types as $type) {

      $children_query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', $type)
        ->condition('field_affiliated_parents', $parent_id);
      $children_result = $children_query->execute();

      if (!empty($children_result)) {
        $list .= '<ul>';
        foreach ($children_result as $child) {
          $node = $this->entityTypeManager->getStorage('node')->load($child);
          $bundle = $this->ldbaseObjectService->isLdbaseCodebook($node->uuid()) ? 'codebook' : $node->bundle();
          $list .= '<li>' .
            t(ucfirst($bundle) . ': ' . $node->getTitle()) .
            '</li>';
          $list .= $this->getChildrenAsHtmlList($node->id());
        }
        $list .= '</ul>';
      }
    }
    return $list;
  }

  /**
   * Return an array of nodes descending from affiliated parent nodes
   */
  private function getChildrenIdsAsArray($parent_id) {
    $node_array = [];
    $children_query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('field_affiliated_parents', $parent_id);
    $children_result = $children_query->execute();

    foreach ($children_result as $child) {
      array_push($node_array, $child);
      $node_array = array_merge($node_array, $this->getChildrenIdsAsArray($child));
    }
    return $node_array;
  }

}
