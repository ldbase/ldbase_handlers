<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LdbaseController extends ControllerBase {

  /**
   * LdbaseController constructor
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * Loads organzation node data into a webform for editing.
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editOrganization(NodeInterface $node) {

    // get node data
    $nid = $node->id();
    $name = $node->getTitle();
    $description = $node->get('body')->value;
    $website = $node->get('field_website')->getValue();
    $locationArray = $node->get('field_location')->getValue();
    $location = [
      'country_code' => $locationArray[0]['country_code'],
      'administrative_area' => $locationArray[0]['administrative_area'],
      'locality' => $locationArray[0]['locality'],
    ];

     $values = [
      'data' => [
        'name' => $name,
        'description' => $description,
        'website' => $website[0]['uri'],
        'location' => $location,
        'node_id' => $nid,
      ]
    ];

    $operation = 'edit';
    // get organization webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_organization');
    $webform = $webform->getSubmissionForm($values, $operation);

    return $webform;
  }
}
