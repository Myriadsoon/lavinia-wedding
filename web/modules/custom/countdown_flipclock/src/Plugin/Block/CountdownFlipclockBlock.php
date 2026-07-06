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
          '#theme' => 'countdown_flipclock',
          '#title' => 'Countdown to the wedding',
          '#units' => [
            'months'=> 0,
            'weeks' => 0,
            'days' => 0,
            'hours' => 0,
          ],
        ];
    }
}
