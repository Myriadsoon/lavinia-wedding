<?php

declare(strict_types=1);

namespace Drupal\wedding_photos\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the wedding photograph upload form.
 */
final class PhotoUploadForm extends FormBase {

  private const PHOTO_BUNDLE = 'wedding_photos';
  private const PHOTO_FIELD = 'field_wedding_photo';
  /**
   * Constructs a PhotoUploadForm
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'wedding_photos_upload_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['selection'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['wedding-photo-upload__selection']],
    ];

    $form['selection']['photo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Photograph'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['wedding-photo-upload__widget']],
      '#accept' => '.jpg,.jpeg,.png,.webp',
      '#upload_location' => 'private://wedding_photos/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'jpg jpeg png webp',
        ],
      ],
      '#required' => TRUE,
    ];

    $form['caption'] = [
      '#wrapper_attributes' => ['class' => ['wedding-photo-upload__caption']],
      "#type" => "textfield",
      "#title" => $this->t('Caption'),
      '#maxlength' => 255,
      "#description" => $this->t('Optional. Add a short caption for your picture.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['wedding-photo-upload__actions']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Share photograph'),
      '#theme_wrappers' => ['input__wedding_photo_share'],
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $fids = $form_state->getValue('photo');

    if (empty($fids[0])) {
      return;
    }

    $node_storage = $this->entityTypeManager->getStorage('node');

    $node = $node_storage->create([
      'type' => self::PHOTO_BUNDLE,
      'title' => $this->t('Wedding photograph'),
      'uid' => $this->currentUser()->id(),
      'moderation_state' => 'pending',
      self::PHOTO_FIELD => [
        'target_id' => $fids[0],
      ],
      'field_caption' => [
        'value' => $form_state->getValue('caption'),
      ],
    ]);

    $node->save();

    $form_state->setIgnoreDestination(TRUE);
    $form_state->setRedirect('wedding_photos.upload_success');

  }
}
