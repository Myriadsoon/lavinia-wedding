<?php

namespace Drupal\wedding_account\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[Block(
  id: 'wedding_account_block_trigger',
  admin_label: new TranslatableMarkup('Wedding Account Trigger'),
  category: new TranslatableMarkup('Wedding'),
)]
class WeddingAccountBlockTrigger extends BlockBase {

  /**
   * {@inheritDoc}
   */
  public function build(): array {
    return [
      '#theme' => 'wedding_account_trigger',
    ];
  }

}
