export default class FlipCard {
  constructor(element) {
    if (!(element instanceof HTMLElement)) {
      throw new TypeError('FlipCard requires a valid HTMLElement.');
    }

    this.element = element;

    this.staticTop = element.querySelector('[data-flipcard-top]');
    this.staticBottom = element.querySelector('[data-flipcard-bottom]');
    this.topFlap = element.querySelector('[data-flipcard-top-flap]');
    this.bottomFlap = element.querySelector('[data-flipcard-bottom-flap]');

    if (
      !this.staticTop ||
      !this.staticBottom ||
      !this.topFlap ||
      !this.bottomFlap
    ) {
      throw new Error('FlipCard is missing one or more required child elements.');
    }

    this.value = this.staticTop.textContent.trim();
  }

  getValue() {
    return this.value;
  }

  destroy() {
    this.element = null;
    this.staticTop = null;
    this.staticBottom = null;
    this.topFlap = null;
    this.bottomFlap = null;
    this.value = null;
  }
}
