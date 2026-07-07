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

  flipTo(nextValue) {
    const normalisedValue = String(nextValue);

    if (normalisedValue === this.value) {
      return;
    }

    this.#prepare(normalisedValue);

    // Temporary, until animation wired in
    this.#finish(normalisedValue);

  }

  #prepare(nextValue) {
    this.#setFace(this.topFlap, this.value);
    this.#setFace(this.bottomFlap, nextValue);
  }

  #finish(nextValue) {
    this.#setFace(this.staticTop, nextValue);
    this.#setFace(this.staticBottom, nextValue);
    this.#setFace(this.topFlap, nextValue);
    this.#setFace(this.bottomFlap, nextValue);

    this.value = nextValue;
  }

  #setFace(element, value) {
    const span = element.querySelector('span');

    if (!span) {
      throw Error('Flipcard face is missing its value span.');
    }

    span.textContent = value
  }
}
