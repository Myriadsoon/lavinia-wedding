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
      throw new Error('FlipCard is missing one or more required face elements.');
    }

    this.staticTopValue = this.staticTop.querySelector('span');
    this.staticBottomValue = this.staticBottom.querySelector('span');
    this.topFlapValue = this.topFlap.querySelector('span');
    this.bottomFlapValue = this.bottomFlap.querySelector('span');

    if (
      !this.staticTopValue ||
      !this.staticBottomValue ||
      !this.topFlapValue ||
      !this.bottomFlapValue
    ) {
      throw new Error('FlipCard is missing one or more required value spans.');
    }

    this.value = this.staticTop.textContent.trim();
  }

  getValue() {
    return this.value;
  }

  flipTo(nextValue) {
    const normalisedValue = String(nextValue);

    if (normalisedValue === this.value || this.isFlipping) {
      return;
    }
    this.isFlipping = true;
    this.#prepare(normalisedValue);

    this.element.classList.add('countdown-flipclock__card--flipping');

    const finish = () =>{
      this.element.classList.remove('countdown-flipclock__card--flipping');
      this.#finish(normalisedValue);
      this.isFlipping = false;
    };

    this.bottomFlap.addEventListener('animationend', finish, {once: true});
  }

  #prepare(nextValue) {
    this.#setValue(this.staticTopValue, nextValue);
    this.#setValue(this.staticBottomValue, this.value);
    this.#setValue(this.topFlapValue, this.value);
    this.#setValue(this.bottomFlapValue, nextValue);
  }

  #finish(nextValue) {
    this.#setValue(this.staticTopValue, nextValue);
    this.#setValue(this.staticBottomValue, nextValue);
    this.#setValue(this.topFlapValue, nextValue);
    this.#setValue(this.bottomFlapValue, nextValue);

    this.value = nextValue;
  }

  #setValue(span, value) {

    if (!span) {
      throw Error('Flipcard face is missing its value span.');
    }

    span.textContent = value
  }
}
