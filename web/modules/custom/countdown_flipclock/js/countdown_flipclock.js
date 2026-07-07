import FlipCard from "./flip-card.js";

(function (Drupal, once) {
  Drupal.behaviors.countdown_flipclock = {
    attach(context) {
      once('countdown-flipclock', '[data-flipcard], context').forEach((element, index) => {
        const card = new FlipCard(element);

        setTimeout(() => {
          card.flipTo(index + 1);
        }, 1000);
      });
    },
  };
})(Drupal, once);
