<?php

namespace Drupal\wedding_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/*
 * Provides the wedding reporting dashboard
 */
class WeddingReportsController extends ControllerBase {

  /**
   * Builds the wedding reporting dashboard
   *
   * @return array
   *   A render array containing the dashboard content.
   */
  public function dashboard(): array {
    return [
      '#theme' => 'wedding_reports_dashboard',
      '#report_groups' => [
        [
          'title' => $this->t('Guest management'),
          'reports' => [
            [
              'title' => $this->t('Guest responses'),
              'description' => $this->t('Review questionnaire responses for wedding guests'),
              'path' => Url::fromRoute('view.wedding_guests_all_responses.page_1')->toString(),
            ],
            [
              'title' => $this->t('Outstanding responses'),
              'description' => $this->t('Identify guests who have not completed their questionnaire'),
              'path' => Url::fromRoute('view.wedding_guests_awaiting_responses.page_1')->toString(),
            ],
            [
              'title' => $this->t('Dietary requirements'),
              'description' => $this->t('Review dietary information for catering arrangements'),
              'path' => Url::fromRoute('view.wedding_guests_dietary_requirements.page_1')->toString(),
            ],
            [
              'title' => $this->t('Gallery viewer accounts'),
              'description' => $this->t('Review gallery-only accounts and their login activity'),
              'path' => Url::fromRoute('view.gallery_viewer_accounts.page_1')->toString(),
            ],
          ],
        ],
        [
          'title' => $this->t('Photography'),
          'reports' => [
            [
              'title' => $this->t('Photo contributions'),
              'description' => $this->t('See how many photographs each guest has uploaded.'),
              'path' => Url::fromRoute('view.photo_contributions.page_1')->toString(),
            ],
          ],
        ],
      ],
    ];
  }
}
