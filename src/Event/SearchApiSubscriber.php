<?php

namespace Drupal\ldbase_handlers\Event;

use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchApiSubscriber implements EventSubscriberInterface {

  public function onQueryPreExecute($event) {
    // if this is the search view page
    if ($event->getQuery()->getSearchId() == 'views_page:search__page_1') {
      $keys = $event->getQuery()->getKeys();
      if (is_null($keys)) {
        $event->getQuery()->sort('ldbase_add_bundle_sort_order', 'ASC');
      }
    }
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'onQueryPreExecute',
    ];
  }

}
