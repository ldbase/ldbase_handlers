<?php

namespace Drupal\ldbase_handlers;

/**
 * Class LDbaseWebformFileStorageService.
 */
class LDbaseWebformFileStorageService implements LDbaseWebformFileStorageServiceInterface {

  /**
   * Constructs a new LDbaseWebformFileStorageService object.
   */
  public function __construct() {
  }
 
  public function transferWebformFile($webform_fid, $ctype) {
    $webform_file = \Drupal\file\Entity\File::load($webform_fid);
    $new_dir = 'private://' . $ctype . 's/';
    \Drupal::service('file_system')->prepareDirectory($new_dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
    $new_copy = file_copy($webform_file, $new_dir . $webform_file->getFileName(), FILE_EXISTS_RENAME);
    file_delete($webform_fid);
    return $new_copy->id();
  }

}
