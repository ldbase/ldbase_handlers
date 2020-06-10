<?php

namespace Drupal\ldbase_handlers;

/**
 * Interface S3fsStorageInterface.
 */
interface S3fsStorageInterface {

  function transferWebformFileToS3fs($fid, $ctype);

}
