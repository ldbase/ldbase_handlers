<?php

namespace Drupal\ldbase_handlers\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds age range values between min and max to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "ldbase_add_age_range",
 *   label = @Translation("LDbase Participant Age Range Field"),
 *   description = @Translation("Add the age range integer values between the min and max to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddAgeRange extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('LDbase Participant Age Range'),
        'description' => $this->t('The inclusive integer values between min and max age range.'),
        'type' => 'integer',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['ldbase_age_range'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();
    // age range only appears on datasets
    if ($entity->bundle() == 'dataset') {
      $ages_min_max = [];
      //get the min and max range for each demographics paragraph on the dataset
      foreach ($entity->field_demographics_information as $delta => $demographics_paragraph) {
        $p = $demographics_paragraph->entity;
        $ages_min_max[$delta] = [$p->get('field_age_range')->from, $p->get('field_age_range')->to];
      }
      if (!empty($ages_min_max)) {
        $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, 'ldbase_age_range');
        foreach ($fields as $field) {
          foreach ($ages_min_max as $min_max) {
            // validation on webform only allows max > min, but just in case
            if ($min_max[0] < $min_max[1]) {
              // add each integer in the range to the index
              for ($i = $min_max[0]; $i <= $min_max[1]; $i++) {
                $field->addValue($i);
              }
            }
          }
        }
      }
    }
  }

}
