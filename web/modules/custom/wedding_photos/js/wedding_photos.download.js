(function (Drupal, once){
  Drupal.behaviors.weddingPhotosDownload = {
    attach(context) {
      once(
        'weddingPhotosDownload',
        '.photoswipe-gallery',
        context,
      ).forEach((gallery) => {
        gallery.addEventListener('photoswipeLightboxBuild', (event) => {
          const lightbox = event.detail.lightbox;

          lightbox.on('change', () => {
            const sourceElement = lightbox.pswp.currSlide.data.element;
            const downloadUrl =
              sourceElement?.dataset.weddingPhotoDownload;

            if (!downloadUrl) {
              return;
            }

            const downloadButton = lightbox.pswp.element.querySelector(
              '.pswp__button--download-button',
            );

            if (downloadButton) {
              downloadButton.href = downloadUrl;
            }
          });
        });
      });
    },
  };
})(Drupal, once);
