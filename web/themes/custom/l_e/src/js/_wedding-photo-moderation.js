(function (Drupal, once) {
  Drupal.behaviors.weddingPhotoModeration = {
    attach(context) {

      once(
        'wedding-photo-moderation',
        '.form--views-form-wedding-photo-moderation-page-1',
        context,
      ).forEach((form) => {
        const categorySelectors = form.querySelectorAll(
          '.wedding-photo-card__category-select',
        );

        const submitButton = form.querySelector(
          'input[type="submit"], button[type="submit"]',
        );

        if (!categorySelectors.length || !submitButton) {
          return;
        }

        const updateButtonState = () => {
          const anySelected = Array.from(categorySelectors).some(
            (select) => select.value !== '',
          );

          submitButton.disabled = !anySelected;
        };

        const updateCardState = (select) => {
          const card = select.closest('.card');

          if (card) {
            card.classList.toggle('is-selected', select.value !== '');
          }
        };

        categorySelectors.forEach((select) => {
          select.addEventListener('change', () => {
            updateCardState(select);
            updateButtonState();
          });

          updateCardState(select);

        });
      });
    },
  }
})(Drupal, once);
