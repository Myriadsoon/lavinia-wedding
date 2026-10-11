<?php

namespace Drupal\wedding_access\EventSubscriber;


use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects annonymous visitors to login after HTML access denial.
 */
final class LoginRedirectSubscriber implements EventSubscriberInterface {

  /*
   * Constructs the subscriber.
   */
  public function __construct(
    private readonly AccountProxyInterface $currentUser,
  ) {}

    /**
   * {@inheritdoc }
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::EXCEPTION => ['onException', 80],
    ];
  }

  /**
   * Redirects anonymous visitors requesting protected HTML pages.
   */
  public function onException(ExceptionEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    if (!$event->getThrowable() instanceof AccessDeniedHttpException) {
      return;
    }

    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    $request = $event->getRequest();

    // Do not redirect non HTML endpoints or from submissions.
    if (!$request->isMethod('GET')) {
      return;
    }

    if ($request->getRequestFormat() !== 'html') {
      return;
    }

    $route_name = RouteMatch::createFromRequest($request)->getRouteName();

    // Only redirect requests for routes protected by wedding_access.
    $route = $request->attributes->get('_route_object');

    if (!$route || !$route->hasRequirement('_wedding_auth_required')) {
      return;
    }

    // Prevent redirect loops and leave registration inaccessible.
    if (!$route_name || str_starts_with($route_name, 'user.reset')
      || in_array($route_name, [
        'user.login',
        'user.pass',
        'user.register',
      ], TRUE)) {
      return;
    }

    // Preserve the original internal path and query string.
    $destination = $request->getPathInfo();
    $query = $request->query->all();
    unset($query['destination']);

    if ($query) {
      $destination .= '?' . http_build_query($query);
    }

    $url = Url::fromRoute('user.login', [], [
      'query' => ['destination' => $destination],
    ])->toString();

    $response = new RedirectResponse($url, 302);
    $response->headers->set('Cache-Control', 'no-store');
    $event->setResponse($response);
  }

}
