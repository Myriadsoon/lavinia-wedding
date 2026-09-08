<?php

namespace Drupal\wedding_guest\Plugin\Block;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a guest response summary block
 *
 */
#[Block(
    id: 'wedding_guest_response_summary',
    admin_label: new TranslatableMarkup('Wedding guest response summary'),
    category: new TranslatableMarkup('Wedding Guest'),
 )]
class GuestResponseSummaryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
    );
  }

  public function build(): array {
    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());

    if (!$account) {
      return [];
    }

    $dietary_status = $account->get('field_dietary_status')->value;
    $dietary_requirements = $account->get('field_dietary_requirements')->value;
    $marriage_advice = $account->get('field_marriage_advice')->value;

    $build = [
      '#theme' => 'wedding_guest_response_summary',
      '#dietary_status' => $dietary_status,
      '#dietary_requirements' => $dietary_requirements,
      '#marriage_advice' => $marriage_advice,
      '#edit_url' => Url::fromRoute('wedding_guest.guest_details')->toString(),
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    \Drupal\Core\Cache\CacheableMetadata::createFromObject($account)
      ->applyTo($build);

    return $build;
  }
}
