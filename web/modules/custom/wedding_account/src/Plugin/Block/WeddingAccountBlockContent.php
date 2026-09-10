<?php

namespace Drupal\wedding_account\Plugin\Block;


use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
    $display_name = $this->currentUser->getDisplayName();
    $account_url = Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()])->toString();
    $logout_url = Url::fromRoute('user.logout')->toString();

    $guest_available = $logged_in && $this->guestStatus !== NULL;
    $guest_complete = $guest_available
      ? $this->guestStatus->isComplete((int) $this->currentUser->id())
      : NULL;

    $guest_details_url = Url::fromRoute('wedding_guest.guest_details')->toString();

    $login_url = Url::fromRoute('user.login')->toString();
    $password_url = Url::fromRoute('user.pass')->toString();

    return [
      '#theme' => 'wedding_account_content',
      '#logged_in' => $logged_in,
      '#display_name' => $logged_in ? $display_name : NULL,
      '#account_url' => $logged_in ? $account_url : NULL,
      '#logout_url' => $logged_in ? $logout_url: NULL,
      '#guest_available' => $guest_available,
      '#guest_complete' => $guest_complete,
      '#guest_details_url' => $logged_in ? $guest_details_url : NULL,
      '#login_url' => !$logged_in ? $login_url : NULL,
      '#password_url' => !$logged_in ? $password_url : NULL,
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

}
