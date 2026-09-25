<?php

declare(strict_types=1);

namespace Drupal\wedding_photos\Plugin\views\field;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;

/**
 * Provides a wedding photograph category selector field.
 */
#[
  ViewsField('wedding_photo_category')
]
final class WeddingPhotoCategory extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * {@inheritdoc }
   */
  protected function getFieldDefinition(): FieldDefinition {
    return '{{ wedding_photo_category_selector }}';
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // This field does not add anything to the Views query.
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL): string {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Returns the name used for this field's View form elements:
   */
  public function form_element_name(): string {
    return $this->options['id'];
  }

  /**
   * Returns the form element row ID.
   */
  public function form_element_row_id($row_id): int {
    return (int) $row_id;
  }

  /**
   * Adds one category selector for each View result row.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state): void {

    if (empty($this->view->result)) {
      return;
    }

    $options = $this->getCategoryOptions();

    $form[$this->options['id']]['#tree'] = TRUE;

    foreach ($this->view->result as $row_index => $row) {
      $form[$this->options['id']][$row_index] = [
        '#type' => 'select',
        '#title' => $this->t('Category'),
        '#title_display' => 'invisible',
        '#options' => $options,
        '#empty_option' => $this->t('- Select category -'),
        '#default_value' => '',
        '#attributes' => [
          'class' => [
            'wedding-photo-card__category-select',
          ],
        ],
      ];

    }
  }

  /**
   * Returns the wedding photograph category options.
   */
  public function getCategoryOptions(): array {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('wedding_photo_categories');

    $options = [];

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return $options;
  }

  /**
   * Publishes categorised photographs from the moderation View
   */
  public function viewsFormSubmit(
    array &$form,
    FormStateInterface $form_state,
  ): void {
    $categories = $form_state->getValue($this->options['id'],) ?? [];

    $current_user = \Drupal::currentUser();

    $transition_validation = \Drupal::service(
      'content_moderation.state_transition_validation',
    );

    foreach ($categories as $row_index => $term_id) {
      // An empty category means this photograph was not selected.
      if (empty($term_id)) {
        continue;
      }

      $row = $this->view->result[$row_index] ?? NULL;
      $node = $row?->_entity;

      if (
        !$node instanceof NodeInterface
        || $node->bundle() !== 'wedding_photos'
        || $node->get('moderation_state')->value !== 'pending'
      ) {
        continue;
      }

      // Confirm this user may transition this node to published.
      $can_publish = false;

      foreach (
        $transition_validation->getValidTransitions($node, $current_user) as $transition
      ) {
        if ($transition->to()->id() === 'published') {
          $can_publish = true;
          break;
        }
      }

      if (!$can_publish) {
        continue;
      }

      $node->set('field_wedding_photo_category', [
        'target_id' => (int) $term_id,
      ]);

      $node->set('moderation_state', 'published');

      $node->setNewRevision(true);
      $node->setRevisionUserId((int) $current_user->id());

      $node->save();
    }
  }

}
