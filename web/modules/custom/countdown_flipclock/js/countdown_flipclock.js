import Display from "./display.js";

(function (Drupal, once) {
  Drupal.behaviors.countdown_flipclock = {
    attach(context) {
      once('countdown-flipclock', '[data-countdown-flipclock], context').forEach((element, index) => {
        const display = new Display(element);

        setTimeout(() => {
          display.update({
            months: 11,
            weeks: 3,
            days: 5,
            hours: 14,
          });
        }, 1000);
      });
    },
  };
})(Drupal, once);
