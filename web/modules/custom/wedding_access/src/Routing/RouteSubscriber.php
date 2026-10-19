<?php

namespace Drupal\wedding_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add authentication requirements to protected routes.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Routes that must remain available without authentication.
   */
  private const PUBLIC_ROUTES = [
    'user.login',
    'user.pass',
    'user.reset',
    'user.reset.form',
    'user.reset.login',
    'user.cancel_confirm',
    'user.well-known.change_password',
  ];

  /**
   * @inheritDoc
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($collection->all() as $route_name => $route) {
      if (in_array($route_name,self::PUBLIC_ROUTES, true)) {
        continue;
      }

      $route->setRequirement('_wedding_auth_required', TRUE);
    }
  }
  /**
   * {@inheritdoc }
   *
   * Assigns our route alteration handler low priority so that
   * our subscriber runs after subscribers with higher priority.
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();

    $events[\Drupal\Core\Routing\RoutingEvents::ALTER] = [
      'onAlterRoutes',
      -1000,
    ];

    return $events;
  }
}


