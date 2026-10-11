<?php

namespace Drupal\wedding_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Requires authentication to access protected wedding routes.
 */
final class WeddingAccessCheck implements AccessInterface {

  /**
   * Checks whether the account is authenticated.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated())
      ->addCacheContexts(['user.roles:authenticated']);
  }

}
