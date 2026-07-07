<?php

namespace Drupal\countdown_flipclock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

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

  public function defaultConfiguration(): array {
    return [
        'title' => 'Countdown clock',
        'target_datetime' => '2027-05-29T14:30:00+01:00',
        'show_months' => TRUE,
        'show_weeks' => TRUE,
        'show_days' => TRUE,
        'show_hours' => TRUE,
      ] + parent::defaultConfiguration();
  }

  public function blockForm($form, FormStateInterface $form_state):array {
    $config = $this->getConfiguration();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['title'],
      '#required' => TRUE,
    ];

    $form['target_datetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target date / time'),
      '#description' => $this->t('Use ISO format, for example 2027-05-29T14:30:00+01:00.'),
      '#default_value' => $config['target_datetime'],
      '#required' => TRUE,
    ];


    foreach (['months', 'weeks', 'days', 'hours'] as $unit) {
      $form["show_$unit"] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show @unit', ['@unit' => ucfirst($unit)]),
        '#default_value' => isset($config["show_$unit"]) ? $config["show_$unit"] : FALSE,
      ];
    }

    return $form;

  }

  public function blockSubmit($form, FormStateInterface $form_state):void {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['target_datetime'] = $form_state->getValue('target_datetime');

    foreach (['months', 'weeks', 'days', 'hours'] as $unit) {
      $this->configuration["show_$unit"] = $form_state->getValue("show_$unit");
    }
  }

  /**
   * @inheritDoc
   */
  public function build(): array {
    $config = $this->getConfiguration();

    $units = [];

    foreach (['months', 'weeks', 'days', 'hours'] as $unit) {
      if (!empty($config["show_$unit"])) {
        $units[$unit] = 0;
      }
    }
    return [
      '#theme' => 'countdown_flipclock',
      '#title' => $config['title'],
      '#target_datetime' => $this->configuration['target_datetime'],
      '#units' => $units,
      '#attached' => [
        'library' => [
          'countdown_flipclock/flipclock',
        ],
      ],
    ];
  }
}
