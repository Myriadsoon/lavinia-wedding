<?php

namespace Drupal\countdown_flipclock\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Countdown FlipClock block.
 *
 * @Block(
 *   id = "countdown_flipclock_block",
 *   admin_label = @Translation("Countdown FlipClock"),
 *   category = @Translation("Countdown FlipClock")
 * )
 */
final class CountdownFlipclockBlock extends BlockBase
{

    /**
     * @inheritDoc
     */
    public function build():array {
        return [
          '#markup' => '<div class="countdown-flipclock" id="countdown_flipclock">Countdown Flipclock is rendering.</div>',
        ];
    }
}
