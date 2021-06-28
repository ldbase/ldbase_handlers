<?php

namespace Drupal\ldbase_handlers;

use Drupal\Core\Session\SessionConfiguration;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sets session cookie lifetime dynamically.
 */
class LdbaseHandlersSessionConfiguration extends SessionConfiguration {

  /**
   * {@inheritdoc}
   */
  public function getOptions(Request $request) {
    $options = parent::getOptions($request);

    // set cookie life to 0 to log out on browser close
    $options['cookie_lifetime'] = 0;

    return $options;
  }

}
