(function (Drupal, once){
  /**
   * Keep the wedding account edit form visible after validation error
   */
  Drupal.behaviors.weddingAccountValidation = {
    attach(context) {
      once('wedding-account-validation', '.wedding-account__edit', context)
        .forEach((panel) => {
          const invalidField = panel.querySelector('[aria-invalid="true"]');

          if (!invalidField) {
            return;
          }

          // Make the form visible immediately, without collapse animation.
          panel.classList.add('show');

          // Keep the toggle's accessibility state consistent.
          const toggle = document.querySelector(
            `[data-bs-target="#${panel.id}"]`
          );

          if (toggle) {
            toggle.setAttribute('aria-expanded', true);
            toggle.classList.remove('collapsed');
          }
      });
    }
  };
})(Drupal, once);
