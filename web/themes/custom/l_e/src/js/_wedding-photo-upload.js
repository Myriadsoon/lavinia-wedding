(function (Drupal, once) {
  const states = new WeakMap();
  const widgetSelector = '.wedding-photo-upload__widget';

  const fileId = (widget) =>
    widget?.querySelector('input[type="hidden"][name="photo[fids]"]')?.value.trim() || '';

  const clearPreview = (state) => {
    if (state.url) {
      URL.revokeObjectURL(state.url);
    }
    state.url = null;
    state.acceptedId = null;
    state.file = null;
  };

  const synchronise = (form, state) => {
    const widget = form.querySelector(widgetSelector);
    if (!widget) {
      return;
    }

    const fid = fileId(widget);
    // Reattaching behaviours before serialization is not an upload response.
    // Only a replacement widget with a new FID confirms server acceptance.
    if (state.pending && state.pending.widget !== widget) {
      const pending = state.pending;
      state.pending = null;
      clearPreview(state);
      if (/^[1-9]\d*$/.test(fid) && fid !== pending.previousId) {
        state.file = pending.file;
        state.acceptedId = fid;
        state.url = URL.createObjectURL(pending.file);
      }
    }

    if (state.acceptedId && fid !== state.acceptedId) {
      clearPreview(state);
    }

    const preview = widget.querySelector('.wedding-photo-upload__preview');
    if (!preview) {
      return;
    }
    if (!state.url || fid !== state.acceptedId) {
      preview.replaceChildren();
      preview.hidden = true;
      return;
    }
    if (preview.querySelector('img')?.getAttribute('src') === state.url) {
      return;
    }

    const image = document.createElement('img');
    image.alt = Drupal.t('Preview of the selected photograph');
    // The browser-local blob is the only image source; never fetch private files.
    image.src = state.url;
    image.addEventListener('error', () => {
      preview.replaceChildren();
      preview.hidden = true;
      if (state.url === image.getAttribute('src')) {
        clearPreview(state);
      }
    }, { once: true });
    preview.replaceChildren(image);
    preview.hidden = false;
  };

  Drupal.behaviors.weddingPhotoUpload = {
    attach(context) {
      const forms = new Set();
      if (context instanceof Element) {
        const form = context.closest('form');
        if (form?.querySelector(widgetSelector)) {
          forms.add(form);
        }
      }
      context.querySelectorAll(widgetSelector).forEach((widget) => {
        if (widget.closest('form')) {
          forms.add(widget.closest('form'));
        }
      });

      once('wedding-photo-upload', [...forms]).forEach((form) => {
        const state = { pending: null, file: null, acceptedId: null, url: null };
        states.set(form, state);

        state.onChange = (event) => {
          const input = event.target;
          if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
            return;
          }
          const widget = input.closest(widgetSelector);
          if (!widget) {
            return;
          }
          // Capture before core's change handlers start AJAX or reject a file.
          state.pending = null;
          clearPreview(state);
          const file = input.files?.[0];
          if (file && /\.(jpe?g|png|webp)$/i.test(file.name)) {
            state.pending = { file, widget, previousId: fileId(widget) };
          }
        };
        state.onPageHide = () => {
          state.pending = null;
          clearPreview(state);
          synchronise(form, state);
        };
        form.addEventListener('change', state.onChange, true);
        window.addEventListener('pagehide', state.onPageHide);
      });

      forms.forEach((form) => synchronise(form, states.get(form)));
    },

    detach(context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }
      // A widget replacement must not discard state held by its surviving form.
      const forms = [...context.querySelectorAll('form')];
      if (context instanceof Element && context.matches('form')) {
        forms.push(context);
      }
      forms.forEach((form) => {
        const state = states.get(form);
        if (state) {
          clearPreview(state);
          state.pending = null;
          form.removeEventListener('change', state.onChange, true);
          window.removeEventListener('pagehide', state.onPageHide);
          states.delete(form);
          once.remove('wedding-photo-upload', form);
        }
      });
    },
  };
})(Drupal, once);
