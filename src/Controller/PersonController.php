<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PersonController extends ControllerBase {

  /**
   * PersonController constructor
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
   * Loads person node data into a webform for editing.
   *
   * @param \Drupal\Node\NodeInterface $node
   */
  public function editPerson(NodeInterface $node) {

    // get node data
    $nid = $node->id();
    $preferred_name = $node->getTitle();
    $description = $node->get('body')->value;
    $location = $node->get('field_location')->getValue();
    $website = $node->get('field_website')->getValue();
    $first_name = $node->get('field_first_name')->value;
    $middle_name = $node->get('field_middle_name')->value;
    $last_name = $node->get('field_last_name')->value;
    $email = $node->get('field_email')->getValue();
    $orcid = $node->get('field_orcid')->value;
    $web_presence = $node->get('field_web_presence')->getValue();
    $professional_titles = $node->get('field_professional_titles')->getValue();
    foreach ($node->get('field_related_organizations')->getValue() as $delta => $value) {
      $related_organizations[$delta] = $value['target_id'];
    }
    $areas_of_expertise = $node->get('field_areas_of_expertise')->getValue();

    $thumbnail = $node->field_thumbnail->entity;
    $thumbnail_fid = !empty($thumbnail) ? $thumbnail->id() : NULL;

    $values = [
      'data' => [
        'node_id' => $nid,
        'preferred_name' => $preferred_name,
        'description' => $description,
        'location' => $location,
        'website' => $website,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'email' => $email,
        'orcid' => $orcid,
        'web_presence' => $web_presence,
        'professional_titles' => $professional_titles,
        'related_organizations' => $related_organizations,
        'areas_of_expertise' => $areas_of_expertise,
        'thumbnail' => $thumbnail_fid,
      ]
    ];

    $operation = 'edit';
    // get webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_person');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }

}