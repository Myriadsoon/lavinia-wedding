<?php

namespace Drupal\wedding_account\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\views\Views;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\wedding_guest\Service\GuestResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Block(
  id: 'wedding_account',
  admin_label: new TranslatableMarkup('Wedding account'),
  category: new TranslatableMarkup('Wedding Account'),
)]
class WeddingAccountBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountProxyInterface $currentUser,
    protected GuestResponse $guestResponse,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFormBuilderInterface $entityFormBuilder,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('wedding_guest.response'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc }
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    // The personal dashboard belongs only on its owner's canonical page.
    $route_user = $this->routeMatch->getParameter('user');
    $is_owner = $account->isAuthenticated()
      && $this->routeMatch->getRouteName() === 'entity.user.canonical'
      && $route_user instanceof UserInterface
      && (int) $route_user->id() === (int) $account->id();

    $access = $is_owner
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    return $access->addCacheContexts(['route', 'user']);
  }

  /**
   * @inheritDoc
   */
  public function build(): array {
    if ($this->currentUser->isAnonymous()) {
      return [];
    }

    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());

    if (!$account) {
      return [];
    }

    $response = $this->guestResponse->getResponse(
      (int) $this->currentUser->id()
    );

    if ($response === NULL) {
      return [];
    }


    $account_form = $this->entityFormBuilder->getForm(
      $account,
      'default',
    );

    $photographs = Views::getView('my_wedding_photographs');

    $photographs_build = [];

    if ($photographs) {
      $photographs_build = $photographs->buildRenderable('block_1');
    }

    $guest_details_url = Url::fromRoute(
      'wedding_guest.guest_details',
    )->toString(TRUE);

    $build = [
      '#theme' => 'wedding_account',
      '#username' => $account->getAccountName(),
      '#email' => $account->getEmail(),
      '#account_form' => $account_form,
      '#guest_response' => $response,
      '#guest_details_url' => $guest_details_url->getGeneratedUrl(),
      '#photo_upload_url' => Url::fromRoute(
        'wedding_photos.upload',
      ),
      '#photographs' => $photographs_build,
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    BubbleableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($account)
      ->addCacheableDependency($guest_details_url)
      ->applyTo($build);

    return $build;

  }

}
