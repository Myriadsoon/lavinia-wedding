<?php

namespace Drupal\wedding_photos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\user\OneTimeAuthentication;

/**
 * Provides a form for inviting wedding gallery viewers.
 */
class GalleryInvitationForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'wedding_photos_gallery_invitation_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('Enter the email address of the person you wish to invite.'),
      '#required' => TRUE,
    ];

    $form['language'] = [
      '#type' => 'radios',
      '#title' => $this->t('Invitation language'),
      '#options' => [
        'en' => $this->t('English'),
        'it' => $this->t('Italian'),
      ],
      '#default_value' => 'en',
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send invitation'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc }
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    // Normalise the email address.
    $email = trim($form_state->getValue('email'));

    $form_state->setValue('email', $email);

    // Check whether an account already uses this email address.
    $accounts = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);

    if (!empty($accounts)) {
      $form_state->setErrorByName(
        'email',
        $this->t('An account already exists with this email address.')
      );
    }
  }

   /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $email = $form_state->getValue('email');
    $langcode = $form_state->getValue('language');

    // Generate a unique username from the email address.
    $username = $email;

    // Create an active gallery-only account.
    $account = User::create([
      'name' => $username,
      'mail' => $email,
      'status' => 1,
      'preferred_langcode' => $langcode,
    ]);

    // Assign the gallery-only role.
    $account->addRole('wedding_gallery_viewer');

    // Save the account before generating it's login URL.
    $account->save();

    // Generate Drupal's secure one-time login URL.
    $login_url = \Drupal::service(OneTimeAuthentication::class)
      ->generateOneTimeLoginUrl($account, [
      'langcode' => $langcode,
    ])->toString();

    // Send the invitation using the Drupal's mail manager.
    $params = [
      'login_url' => $login_url,
    ];

    $result = \Drupal::service('plugin.manager.mail')->mail(
      'wedding_photos',
      'gallery_invitation',
      $email,
      $langcode,
      $params
    );

    if (!empty($result['result'])) {
      $this->messenger()->addStatus(
        $this->t('The invitation was sent to %email.', ['%email' => $email])
      );
    }
  }

}
