<?php

declare(strict_types=1);

namespace Drupal\wedding_photos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Provides the photograph upload completion page.
 */
final class PhotoUploadSuccessController extends ControllerBase {

  /**
   * Builds a generic confirmation with language-aware action links.
   */
  public function build(): array {
    return [
      '#theme' => 'wedding_photos_upload_success',
      '#share_another' => [
        '#type' => 'link',
        '#title' => $this->t('Share another photograph'),
        '#url' => Url::fromRoute('wedding_photos.upload'),
        '#attributes' => ['class' => ['btn', 'btn-primary']],
      ],
      '#view_gallery' => [
        '#type' => 'link',
        '#title' => $this->t('View gallery'),
        '#url' => Url::fromRoute('view.wedding_photos_gallery.page_1'),
        '#attributes' => ['class' => ['btn', 'btn-outline-secondary']],
      ],
      '#cache' => [
        'contexts' => ['languages:language_interface', 'languages:language_url'],
      ],
    ];
  }

}
