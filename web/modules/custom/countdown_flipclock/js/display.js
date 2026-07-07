import FlipCard from "./flip-card.js";

export default class Display {
  constructor(element) {
    if (!(element instanceof HTMLElement)) {
      throw new TypeError('Display requires a valid HTML element.');
    }

    this.element = element;
    this.cards = {};

    element.querySelectorAll('[data-countdown-unit]').forEach((unitElement) => {
      const unit = unitElement.dataset.countdownUnit;
      const cardElement = unitElement.querySelector('[data-flipcard]');

      if (!unit || !cardElement) {
        return;
      }

      this.cards[unit] = new FlipCard(cardElement);
    });
  }

  update(values) {
    Object.entries(values).forEach(([unit, value]) => {
      if (!this.cards[unit]) {
        return;
      }

      this.cards[unit].flipTo(value);
    });
  }

  getCards() {
    return this.cards;
  }
}
