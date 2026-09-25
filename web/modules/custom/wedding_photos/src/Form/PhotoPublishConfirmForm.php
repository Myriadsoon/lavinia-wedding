<?php

namespace Drupal\wedding_photos\Form;


use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PhotoPublishConfirmForm extends ConfirmFormBase {

  /**
   * The node ID being moderated
   */
  protected ?int $nodeId = null;

  /**
   * Construct the publish confirmation form
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    private AccountProxyInterface $currentUser,
    protected StateTransitionValidationInterface $transitionValidation,
  ) {}

  /**
   * {@inheritdoc }
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('content_moderation.state_transition_validation'),
    );
  }

  /**
   * {@inheritdoc }
   */
  public function getFormId(): string {
    return 'wedding_photos_publish_confirm_form';
  }

  /**
   * {@inheritdoc }
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    ?NodeInterface $node = NULL,
  ): array {
    if (
      !$node
      || $node->bundle() !== 'wedding_photos'
      || $node ->get('moderation_state')->value !== 'pending'
      || !$this->canPublish($node)
    ) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    $this->nodeId = (int) $node->id();

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function getQuestion(): string {
    return (string) $this->t('Publish this photograph?');
  }

  /**
   * {@inheritdoc }
   */
  public function getDescription(): string {
    return (string) $this->t('Once published this photograph will be
    available so people can view the wedding photo gallery.');
  }

  /**
   * {@inheritdoc }
   */
  public function getConfirmText(): string {
    return (string) $this->t('Publish');
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl(): Url {
    return Url::fromUserInput('/photos/moderate');
  }

  /**
   * Checks whether the current user may publish the photograph
   */
  protected function canPublish(NodeInterface $node): bool {
    foreach ($this->transitionValidation->getValidTransitions(
      $node,
      $this->currentUser,
    ) as $transition) {
      if ($transition->to()->id() === 'published') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->load($this->nodeId);

    if (
      !$node instanceof NodeInterface
      ||$node->bundle() !== 'wedding_photos'
      || $node->get('moderation_state')->value !== 'pending'
      || !$this->canPublish($node)
    ) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    $node->set('moderation_state', 'published');
    $node->setNewRevision(TRUE);
    $node->setRevisionUserId($this->currentUser->id());
    $node->save();

    $this->messenger()->addStatus(
      $this->t('The photograph has been published..'
      )
    );

    $form_state->setRedirectUrl(
      Url::fromUserInput('/photos/moderate')
    );
  }

}
