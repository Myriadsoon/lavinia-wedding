export default class Countdown {
  constructor(targetDate) {
    this.target = new Date(targetDate).getTime();

    if (Number.isNaN(this.target)) {
      throw new Error('Countdown requires a valid target date.');
    }
  }

  getRemaining() {
    const now = Date.now();
    const diff = Math.max(this.target - now, 0);

    const totalHours = Math.floor(diff / (1000 * 60 * 60));
    const totalDays = Math.floor(totalHours / 24);

    const months = Math.floor(totalDays / 30);
    const remainingDaysAfterMonths = totalDays % 30;

    const weeks = Math.floor(remainingDaysAfterMonths / 7);
    const days = remainingDaysAfterMonths % 7;

    const hours = totalHours % 24;

    return {
      months,
      weeks,
      days,
      hours,
    };
  }

  hasExpired() {
    return Date.now() >= this.target;
  }
}
