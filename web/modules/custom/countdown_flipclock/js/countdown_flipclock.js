import FlipCard from "./flip-card.js";

(function (Drupal, once) {
  Drupal.behaviors.countdownFlipclock = {
    attach(context) {
      once('countdown-flipclock', '[data-flipcard], context').forEach((element) => {
        const card = new FlipCard(element);
        console.log('Flipcard discovered: ', card.getValue(), card);
      });
    },
  };
})(Drupal, once);
