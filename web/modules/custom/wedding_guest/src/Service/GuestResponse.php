<?php

namespace Drupal\wedding_guest\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class GuestResponse {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Returns a user's wedding guest responses.
   */
  public function getResponse(int $uid): ?array {
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($uid);

    if (!$user) {
      return NULL;
    }

    return [
      'dietary_status' => $user->get('field_dietary_status')->value,
      'dietary_requirements' => $user->get('field_dietary_requirements')->value,
      'marriage_advice' => $user->get('field_marriage_advice')->value,
    ];
  }
}
