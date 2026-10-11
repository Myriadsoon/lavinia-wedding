<?php

namespace Drupal\wedding_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add authentication requirements to protected routes.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @inheritDoc
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.canonical')) {
      $route->setRequirement('_wedding_auth_required', TRUE);
    }
  }

}
