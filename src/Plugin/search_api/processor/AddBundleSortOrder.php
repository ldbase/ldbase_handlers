<?php

namespace Drupal\ldbase_handlers\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds sort priority based on LDbase content type.
 *
 * @SearchApiProcessor(
 *   id = "ldbase_add_bundle_sort_order",
 *   label = @Translation("LDbase Bundle Sort Order"),
 *   description = @Translation("Adds sort priority based on LDbase content type."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddBundleSortOrder extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('LDbase Bundle Sort Order'),
        'description' => $this->t('The predetermined sort order for content types.'),
        'type' => 'integer',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['ldbase_add_bundle_sort_order'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();

    $content_type = $entity->bundle();

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'ldbase_add_bundle_sort_order');
    foreach ($fields as $field) {
      switch ($content_type) {
        case 'project':
          $sort_value = 1;
          break;
        case 'dataset':
          $sort_value = 2;
          break;
        case 'code':
          $sort_value = 3;
          break;
        case 'document':
          $sort_value = 4;
          break;
        case  'person':
          $sort_value = 98;
          break;
        case 'organization':
          $sort_value = 99;
          break;
        default:
          $sort_value = 100;
      }
      $field->addValue($sort_value);
    }
  }

}
