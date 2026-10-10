<?php

namespace Drupal\wedding_account\Plugin\Block;


use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\wedding_guest\Service\GuestStatus;

#[Block(
  id: 'wedding_account_block_content',
  admin_label: new TranslatableMarkup('Wedding Account Content'),
  category: new TranslatableMarkup('Wedding'),
)]

class WeddingAccountBlockContent extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?GuestStatus $guestStatus = null,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->has('wedding_guest.status')
        ? $container->get('wedding_guest.status')
        : NULL,
    );

  }
  /**
   * @inheritDoc
   */
  public function build() {
    $logged_in = $this->currentUser->isAuthenticated();
    $metadata = new BubbleableMetadata();
    $metadata->addCacheContexts(['user']);

    if ($logged_in) {
      $account = $this->entityTypeManager->getStorage('user')
        ->load($this->currentUser->id());
      if ($account) {
        $metadata->addCacheableDependency($account);
      }
    }

    // Retain URL cacheability and attachments, including logout placeholders.
    $generate_url = static function (Url $url) use ($metadata): string {
      $generated_url = $url->toString(TRUE);
      $metadata->addCacheableDependency($generated_url);
      return $generated_url->getGeneratedUrl();
    };

    $display_name = $this->currentUser->getDisplayName();
    $account_url = $logged_in
      ? $generate_url(Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()]))
      : NULL;
    $logout_url = $logged_in
      ? $generate_url(Url::fromRoute('user.logout'))
      : NULL;

    $guest_available = $logged_in && $this->guestStatus !== NULL;
    $guest_complete = $guest_available
      ? $this->guestStatus->isComplete((int) $this->currentUser->id())
      : NULL;

    $guest_details_url = $generate_url(Url::fromRoute('wedding_guest.guest_details'));

    $photo_upload_route = Url::fromRoute('wedding_photos.upload');
    $can_upload_photos = FALSE;
    if ($logged_in) {
      $upload_access = $photo_upload_route->access($this->currentUser, TRUE);
      $metadata->addCacheableDependency($upload_access);
      $can_upload_photos = $upload_access->isAllowed();
    }
    $photo_upload_url = $can_upload_photos
      ? $generate_url($photo_upload_route)
      : NULL;

    $photo_moderation_route = Url::fromRoute('view.wedding_photo_moderation.page_1');
    $can_moderate_photos = FALSE;
    if ($logged_in) {
      $moderation_access = $photo_moderation_route->access($this->currentUser, TRUE);
      $metadata->addCacheableDependency($moderation_access);
      $can_moderate_photos = $moderation_access->isAllowed();
    }
    $photo_moderation_url = $can_moderate_photos
      ? $generate_url($photo_moderation_route)
      : NULL;

    $login_url = $generate_url(Url::fromRoute('user.login'));
    $password_url = $generate_url(Url::fromRoute('user.pass'));

    $build = [
      '#theme' => 'wedding_account_content',
      '#logged_in' => $logged_in,
      '#display_name' => $logged_in ? $display_name : NULL,
      '#account_url' => $logged_in ? $account_url : NULL,
      '#logout_url' => $logged_in ? $logout_url: NULL,
      '#guest_available' => $guest_available,
      '#guest_complete' => $guest_complete,
      '#guest_details_url' => $logged_in ? $guest_details_url : NULL,
      '#can_upload_photos' => $can_upload_photos,
      '#photo_upload_url' => $photo_upload_url,
      '#can_moderate_photos' => $can_moderate_photos,
      '#photo_moderation_url' => $photo_moderation_url,
      '#login_url' => !$logged_in ? $login_url : NULL,
      '#password_url' => !$logged_in ? $password_url : NULL,
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    BubbleableMetadata::createFromRenderArray($build)
      ->merge($metadata)
      ->applyTo($build);

    return $build;
  }

}
