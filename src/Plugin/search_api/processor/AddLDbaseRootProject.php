<?php

namespace Drupal\ldbase_handlers\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\ldbase_content\LDbaseObjectService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the LDbase root project to indexed data
 *
 * @SearchApiProcessor (
 *   id = "add_ldbase_root_project",
 *   label = @Translation("LDbase Root Project"),
 *   description = @Translation("Adds the LDbase Root Project. <em>Only applies to Datasets, Code, and Documents</em>"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */

class AddLDbaseRootProject extends ProcessorPluginBase {

  /**
   * @var LDbaseObjectService $ldbaseObjectService
   */
  protected $ldbaseObjectService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LDbaseObjectService $ldbaseObjectService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ldbaseObjectService = $ldbaseObjectService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ldbase.object_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('LDbase Root Project'),
        'description' => $this->t('The root project that holds this item.'),
        'type' => 'string',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['ldbase_root_project'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();
    // only used for datasets, code, and documents
    $valid_bundles = ['dataset','code','document'];
    if (in_array($entity->bundle(), $valid_bundles)) {
      $nid = $entity->id();
      $root_project_node = $this->ldbaseObjectService->getLdbaseRootProjectNodeFromLdbaseObjectNid($nid);
      if (!empty($root_project_node)) {
        $root_project_title = $root_project_node->getTitle();
        $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, 'ldbase_root_project');
        foreach ($fields as $field) {
          $field->addValue($root_project_title);
        }
      }
    }
  }

}
