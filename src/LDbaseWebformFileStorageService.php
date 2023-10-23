<?php

namespace Drupal\ldbase_handlers;

use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class LDbaseWebformFileStorageService.
 */
class LDbaseWebformFileStorageService implements LDbaseWebformFileStorageServiceInterface {

  protected $file_system;

  protected $file_repository;

  /**
   * Constructs a new LDbaseWebformFileStorageService object.
   */
  public function __construct(FileSystemInterface $file_system, FileRepositoryInterface $file_repository) {
    $this->file_system = $file_system;
    $this->file_repository = $file_repository;
  }

  public function transferWebformFile($original_fid, $ctype) {
    $original_file = File::load($original_fid);
    $original_file_uri = $original_file->getFileUri();
    if (strpos($original_file_uri, "private://{$ctype}s/") !== 0) {
      $new_dir = 'private://' . $ctype . 's/' . date('Y-m', time()) . '/';
      $this->file_system->prepareDirectory($new_dir, FileSystemInterface::CREATE_DIRECTORY);
      $new_copy = $this->file_repository->copy($original_file, $new_dir . $original_file->getFileName(), FileSystemInterface::EXISTS_RENAME);
      $this->file_system->delete($original_fid);
      return $new_copy->id();
    }
    else {
      return $original_fid;
    }
  }

}
