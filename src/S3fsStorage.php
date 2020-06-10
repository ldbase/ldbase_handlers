<?php

namespace Drupal\ldbase_handlers;

/**
 * Class S3fsStorage.
 */
class S3fsStorage implements S3fsStorageInterface {

  /**
   * Constructs a new S3fsStorage object.
   */
  public function __construct() {
  }
 
  public function transferWebformFileToS3fs($fid, $ctype) {
    $file = \Drupal\file\Entity\File::load($fid);
    $s3_dir = 's3://' . $ctype . 's/' . $fid . '/';
    \Drupal::service('file_system')->prepareDirectory($s3_dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
    $s3_destination = 's3://datasets/' . $file->getFileName();
    $s3_copy = file_copy($file, $s3_dir . $file->getFileName(), FILE_EXISTS_RENAME);
    file_delete($file_id);
    return $s3_copy->id();
  }

}
