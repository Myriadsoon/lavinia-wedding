<?php

namespace Drupal\wedding_guest\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class GuestStatus {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   *  Determines whether a user has completed the guest form.
   */
  public function isComplete(int $uid): bool {
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($uid);

    if (!$user) {
      return FALSE;
    }

    return !$user->get('field_dietary_status')->isEmpty();
  }
}
