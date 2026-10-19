<?php

namespace Drupal\wedding_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Controls access to protected wedding routes.
 */
final class WeddingAccessCheck implements AccessInterface {

  /**
   * Route paths accessible to gallery-only visitors.
   */
  private const GALLERY_ALLOWED_PATHS = [
    '/photos/gallery',
    '/system/files/{filepath}',
    '/system/files/{scheme}',
    '/system/files/styles/{image_style}/{scheme}',
    '/user/logout',
    '/user/logout/confirm',
  ];

  /**
   * Checks whether the account may access the requested route.
   */
  public function access(
    AccountInterface $account,
    Route $route,
  ): AccessResult {
    // Anonymous visitors cannot access protected routes.
    if ($account->isAnonymous()) {
      return AccessResult::forbidden()
        ->cachePerUser();
    }

    $roles = $account->getRoles();

    // Restrict users whose only wedding access role is gallery viewer.
    $gallery_only = in_array('wedding_gallery_viewer', $roles, TRUE)
      && !in_array('wedding_guest', $roles, TRUE)
      && !in_array('wedding_photo_moderator', $roles, TRUE)
      && !in_array('administrator', $roles, TRUE);

    if ($gallery_only) {
      $path = $route->getPath();

      // Allow gallery access, protect images and logout.
      if (in_array($path, self::GALLERY_ALLOWED_PATHS, TRUE)) {
        return AccessResult::allowed()->cachePerUser();
      }

      // Allow gallery viewers to manage their own account.
      if (in_array($path, [
        '/user/{user}',
        '/user/{user}/edit',
      ], TRUE)) {
        $route_user = \Drupal::routeMatch()->getParameter('user');

        $is_owner = $route_user instanceof \Drupal\user\UserInterface
          && (int) $route_user->id() === (int) $account->id();

        return AccessResult::allowedIf($is_owner)
          ->cachePerUser()
          ->addCacheContexts(['route']);
      }

      // Deny all other protected routes.
      return AccessResult::forbidden()->cachePerUser();

    }

    // Other authenticated users retain their existing access.
    return AccessResult::allowed()
      ->cachePerUser();
  }

}
