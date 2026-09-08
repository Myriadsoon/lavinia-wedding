<?php

namespace Drupal\wedding_guest\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the guest details form
 */
class GuestDetailsForm extends FormBase {

  /**
   *The current user
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'wedding_guest_guest_details_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());

    $step = $form_state->get('step') ?? 1;

    $form['#prefix'] = '<div id="guest-details-form-wrapper" class="wedding-guest">';
    $form['#suffix'] = '</div>';

    $form['progress'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['wedding-guest__progress'],
        'aria-label' => t('Form progress'),
      ],
    ];

    $form['progress']['step_1'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => '01',
      '#attributes' => [
        'class' => [
          'wedding-guest__progress-step',
          $step === 1
            ? 'wedding-guest__progress-step--active'
            : 'wedding-guest__progress-step--complete',
        ],
        'aria-current' => $step === 1 ? 'step': NULL,
      ],
    ];

    $form['progress']['divider'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => '',
      '#attributes' => [
        'class' => ['wedding-guest__progress-divider'],
        'aria-hidden' => 'true',
      ],
    ];

    $form['progress']['step_2'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => '02',
      '#attributes' => [
        'class' => [
          'wedding-guest__progress-step',
          $step === 2
            ? 'wedding-guest__progress-step--active'
            : 'wedding-guest__progress-step--pending',
        ],
        'aria-current' => $step === 2 ? 'step': NULL,
      ],
    ];


    if ($step == 1) {
      $form['step_content'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'wedding-guest__step',
            'wedding-guest__step--dietary'
          ],
        ],
      ];

      $form['step_content']['dietary_status'] = [
        '#type' => 'radios',
        '#title' => $this->t('Do you have any dietary requirements you would like to advise us of?'),
        '#options' => [
          'no' => $this->t('No'),
          'yes' => $this->t('Yes'),
        ],
        '#defult_value' => $form_state->get('dietary_status')
          ?? $account->get('field_dietary_status')->value,
      ];

      $form['step_content']['dietary_requirements'] = [
        '#type' => 'textarea',
        '#title' => $this->t('  Please tell us about your dietary Requirements'),
        '#default_value' => $form_state->get('dietary_requirements')
          ?? $account->get('field_dietary_requirements')->value,
        '#states' => [
          'visible' => [
            ':input[name="dietary_status"]' => ['value' => 'yes'],
          ],
        ],
      ];

      $form['step_content']['actions'] = [
        '#type' => 'actions'
      ];

      $form['step_content']['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
        '#submit' => ['::nextStep'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'guest-details-form-wrapper',
        ],
      ];
    }
    else {

      $form['step_content'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'wedding-guest__step',
            'wedding-guest__step--advice',
          ],
        ],
      ];

      $form['step_content']['marriage_advice'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Would you like to share any marriage advice for us newlyweds?'),
        '#default_value' =>$form_state->getValue('marriage_advice')
          ?? $account->get('field_marriage_advice')->value,
      ];

      $form['step_content']['actions'] = [
        '#type' => 'actions'
      ];

      $form['step_content']['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#button_type' => 'secondary',
        '#submit' => ['::previousStep'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'guest-details-form-wrapper',
        ],
      ];

      $form['step_content']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ];
  }

    return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (
      $form_state->getValue('dietary_status') === 'yes'
      && trim((string)$form_state->getValue('dietary_requirements')) === ''
    ) {
      $form_state->setErrorByName(
        'dietary_requirements',
        $this->t('As you have selected "Yes" to this question, have you overlooked filling in what your requirements are?')
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($this->currentUser->id());

    $dietary_status = $form_state->get('dietary_status')
      ?? $account->get('field_dietary_status')->value;
    $dietary_requirements =$form_state->get('dietary_requirements')
      ?? $account->get('field_dietary_requirements')->value;

    $account->set('field_dietary_status', $dietary_status);

    if ($dietary_status === 'yes') {
      $account->set(
        'field_dietary_requirements',
        $dietary_requirements
      );
    } else {
      $account->set(
        'field_dietary_requirements',
        NULL
      );
    }

    $account->set(
      'field_marriage_advice',
      $form_state->getValue('marriage_advice')
    );

    $account->save();

    $this->messenger()->addStatus(
      $this->t('Thank you, your responses have been saved.
        You can review and change your submissions at any time, please see your
        user account page.')
    );
  }

  public function nextStep(array &$form, FormStateInterface $form_state): void {
    $form_state->set(
      'dietary_status',
      $form_state->getValue('dietary_status')
    );

    $form_state->set(
      'dietary_requirements',
      $form_state->getValue('dietary_requirements')
    );

    $form_state->set('step', 2);
    $form_state->setRebuild();
  }

  public function previousStep(array &$form, FormStateInterface $form_state): void {
    $form_state->set('step', 1);
    $form_state->setRebuild();
  }

  public function ajaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

}
