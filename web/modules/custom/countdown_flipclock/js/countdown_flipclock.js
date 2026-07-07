import Countdown from "./countdown.js";
import Display from "./display.js";

(function (Drupal, once) {
  Drupal.behaviors.countdown_flipclock = {
    attach(context) {
      once('countdown-flipclock', '[data-countdown-flipclock], context').forEach((element, index) => {
        const target = element.dataset.countdownTarget;

        if (!target) {
          return;
        }

        const countdown = new Countdown(target);
        const display = new Display(element);

        const update = () => {
          display.update(countdown.getRemaining());
        };

        update();
        setInterval(update, 60 *1000);
      });
    },
  };
})(Drupal, once);
