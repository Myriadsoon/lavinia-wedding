
/**
 * @file
 * Converts HEIC/HEIF photographs to JPEG and manages upload states.
 */

(function (Drupal, once) {
  'use strict';

  const HEIC_TYPES = [
    'image/heic',
    'image/heif',
    'image/heic-sequence',
    'image/heif-sequence'
  ];

  Drupal.behaviors.weddingPhotosHeicUpload = {
    attach(context) {
      once(
        'wedding-photos-upload-state',
        'form:has(.wedding-photo-upload__widget)',
        context
      ).forEach((form) => {
        let state = 'idle';
        let conversionToken = 0;
        let uploadPending = false;
        let observer;

        const getWidget = () =>
          form.querySelector('.wedding-photo-upload__widget');

        const getInput = () =>
          getWidget()?.querySelector('input[type="file"]');

        const getFileId = () =>
          form.querySelector('[name="photo[fids]"]')?.value.trim();

        const getSubmit = () =>
          form.querySelector('.wedding-photo-upload__submit');

        const getPicker = () =>
          form.querySelector('.wedding-photo-upload__choose');

        function getStatus() {
          let status = form.querySelector(
            '.wedding-photo-upload__heic-status'
          );

          if (!status) {
            status = document.createElement('p');
            status.className = 'wedding-photo-upload__heic-status';
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');

            const widget = getWidget();

            if (widget) {
              widget.insertAdjacentElement('afterend', status);
            }
          }

          return status;
        }

        function setState(nextState, message = '') {
          state = nextState;

          const busy =
            state === 'converting' ||
            state === 'uploading';

          const submit = getSubmit();
          const picker = getPicker();

          if (submit) {
            submit.disabled = state !== 'ready';
          }

          // Keep the file input enabled for Drupal's AJAX handler.
          if (picker) {
            picker.classList.toggle(
              'wedding-photo-upload__choose--busy',
              busy
            );
          }

          form.setAttribute('aria-busy', String(busy));

          const status = getStatus();

          if (status) {
            status.textContent = message;
            status.classList.toggle(
              'wedding-photo-upload__heic-status--error',
              state === 'error'
            );
          }
        }

        function isHeic(file) {
          return /\.(heic|heif)$/i.test(file.name) ||
            HEIC_TYPES.includes(file.type.toLowerCase());
        }

        function reconcileWidget() {
          const fileId = getFileId();

          if (fileId) {
            uploadPending = false;

            if (state !== 'ready') {
              setState(
                'ready',
                Drupal.t('Photograph ready to share.')
              );
            }
            return;
          }

          if (
            uploadPending &&
            !getWidget()?.querySelector(
              'input[name="photo_upload_button"]'
            )
          ) {
            // Do not infer failure while the widget is being replaced.
            return;
          }

          if (state === 'ready' && !fileId) {
            setState('idle');
          }
        }

        async function convertHeic(file, input) {
          const token = ++conversionToken;

          setState(
            'converting',
            Drupal.t('Preparing your photograph…')
          );

          input.value = '';

          try {
            if (typeof window.heic2any !== 'function') {
              throw new Error('HEIC converter unavailable.');
            }

            const result = await window.heic2any({
              blob: file,
              toType: 'image/jpeg',
              quality: 0.85
            });

            if (token !== conversionToken) {
              return;
            }

            const blob = Array.isArray(result)
              ? result[0]
              : result;

            if (!(blob instanceof Blob)) {
              throw new Error('Invalid conversion result.');
            }

            const jpegName = /\.(heic|heif)$/i.test(file.name)
              ? file.name.replace(/\.(heic|heif)$/i, '.jpg')
              : `${file.name}.jpg`;

            const jpegFile = new File(
              [blob],
              jpegName,
              {
                type: 'image/jpeg',
                lastModified: Date.now()
              }
            );

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(jpegFile);
            input.files = dataTransfer.files;

            if (
              input.files.length !== 1 ||
              input.files[0].type !== 'image/jpeg'
            ) {
              throw new Error('Unable to assign converted file.');
            }

            // Dispatch the JPEG selection through Drupal's
            // existing managed_file AJAX upload mechanism.
            uploadPending = true;

            setState(
              'uploading',
              Drupal.t('Uploading your photograph…')
            );

            input.dispatchEvent(
              new Event('change', { bubbles: true })
            );
          }
          catch (error) {
            if (token !== conversionToken) {
              return;
            }

            console.error(
              '[Wedding photos] HEIC conversion failed:',
              error
            );

            input.value = '';
            uploadPending = false;

            setState(
              'error',
              Drupal.t(
                "We couldn't prepare this photograph. Please try again or choose another."
              )
            );
          }
        }

        // Capture file selections before Drupal's auto-upload handler.
        form.addEventListener('change', (event) => {
          const input = event.target;

          if (
            !input.matches?.(
              '.wedding-photo-upload__widget input[type="file"]'
            )
          ) {
            return;
          }

          const file = input.files?.[0];

          if (!file) {
            return;
          }

          if (state === 'converting') {
            event.stopImmediatePropagation();
            event.preventDefault();
            input.value = '';
            return;
          }

          if (isHeic(file)) {
            event.stopImmediatePropagation();
            event.preventDefault();

            convertHeic(file, input);
            return;
          }

          // Standard image: allow Drupal to perform its upload.
          uploadPending = true;

          setState(
            'uploading',
            Drupal.t('Uploading your photograph…')
          );
        }, true);

        // Keep the state in sync with AJAX widget replacement.
        observer = new MutationObserver(() => {
          reconcileWidget();
        });

        observer.observe(form, {
          childList: true,
          subtree: true
        });

        // Initialise from the actual managed_file state.
        if (getFileId()) {
          setState(
            'ready',
            Drupal.t('Photograph ready to share.')
          );
        }
        else {
          setState('idle');
        }
      });
    }
  };

})(Drupal, once);


