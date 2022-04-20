<?php

namespace Drupal\ldbase_handlers\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form for publishing all items in a project.
 */
class ConfirmPublishAllForm extends ConfirmFormBase {

  /**
   * The LDbase publish status service
   * @var \Drupal\ldbase_handlers\PublishStatusService
   */
  protected $publishStatusService;

  /**
   * UUID of project
   * @param Drupal\node\Entity\Node $node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->publishStatusService = $container->get('ldbase_handlers.publish_status_service');
    return $instance;
  }

  /**
   * [@inheritdoc]
   */
  public function getFormId() : string {
    return 'ldbase_handlers_confirm_publish_all_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $node_id = $this->node->id();
    $route = 'entity.node.canonical';
    $url_parameters = [
      'node' => $node_id,
    ];
    $url = Url::fromRoute($route, $url_parameters);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Publish all items in this Project: %project_title?', ['%project_title' => $this->node->getTitle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $message = '<p>' . t('If you confirm, all the metadata and data in this project hierarchy will be available to the public.') . '</p>';
    $message .= '<p>' . t('Any embargoed files will still be embargoed.') . '</p>';

    $description = "<div class='publish-all--confirmation'>" . $message . "</div>";
    return Markup::create($description);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL) {
    $this->node = $node;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // make sure this is a project
    if ($this->node->bundle() == 'project') {
      $changed_nodes = $this->publishStatusService->publishNodeAndChildren($this->node->id());
      $message = t("You published %count items.", ['%count' => count($changed_nodes)]);
    }
    else {
      $this->messenger()->addError($this->t('An invalid id was passed.'));
    }

    $this->messenger()->addStatus($message);
    $url = Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    $form_state->setRedirectUrl($url);
  }

}
