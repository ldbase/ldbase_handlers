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
 
  public function transferWebformFile($original_fid, $ctype) {
    $original_file = \Drupal\file\Entity\File::load($original_fid);
    $original_file_uri = $original_file->getFileUri();
    dsm("Ctype: {$ctype}");
    dsm("File URI: {$original_file_uri}");
    if (strpos($original_file_uri, "private://{$ctype}s/") !== 0) {
      dsm("Making a new copy of the file");
      $new_dir = 'private://' . $ctype . 's/' . date('Y-m', time()) . '/';
      \Drupal::service('file_system')->prepareDirectory($new_dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
      $new_copy = file_copy($original_file, $new_dir . $original_file->getFileName(), FILE_EXISTS_RENAME);
      file_delete($original_fid);
      return $new_copy->id();
    }
    else {
      dsm("File is already in the correct place");
      return $original_fid;
    }
  }

}
