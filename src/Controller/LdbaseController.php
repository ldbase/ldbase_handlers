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
    $websiteArray = $node->get('field_website')->getValue();
    foreach ($websiteArray as $key => $value) {
      $website[$key] = $value['uri'];
    }
    $locationArray = $node->get('field_location')->getValue();
    foreach ($locationArray as $key=>$value) {
      $location[$key] = $value;
    }
    $image = $node->field_thumbnail->entity;
    $image_fid = !empty($image) ? $image->id() : NULL;

     $values = [
      'data' => [
        'name' => $name,
        'description' => $description,
        'website' => $website,
        'location' => $location,
        'node_id' => $nid,
        'image' => $image_fid,
      ]
    ];

    $operation = 'edit';
    // get organization webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_organization');
    $webform = $webform->getSubmissionForm($values, $operation);

    return $webform;
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
    $locationArray = $node->get('field_location')->getValue();
    foreach ($locationArray as $key=>$value) {
      $location[$key] = $value;
    }
    $websiteArray = $node->get('field_website')->getValue();
    foreach ($websiteArray as $key => $value) {
      $website[$key] = $value['uri'];
    }
    $first_name = $node->get('field_first_name')->getValue()[0];
    $middle_name = $node->get('field_middle_name')->getValue()[0];
    $last_name = $node->get('field_last_name')->getValue()[0];
    $emailArray = $node->get('field_email')->getValue();
    foreach ($emailArray as $key => $value) {
      $email[$key] = $value;
    }
    $orcid = $node->get('field_orcid')->getValue()[0];
    $web_presence = $node->get('field_web_presence')->getValue();
    $professional_titles = $node->get('field_professional_titles')->getValue();
    $related_organizations_array = $node->get('field_related_organizations')->getValue();
    foreach ($related_organizations_array as $key => $value) {
      foreach ($value as $k2 => $v2) {
        $related_organizations[$key] = $v2;
      }
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
    // get organization webform and load values
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('create_update_person');
    $webform = $webform->getSubmissionForm($values, $operation);
    return $webform;
  }
}
