export class PlaybackClock {
  constructor(
    check,
    {
      now = () => performance.now(),
      schedule = (callback, delay) => setTimeout(callback, delay),
      cancel = (timer) => clearTimeout(timer),
    } = {},
  ) {
    this.check = check;
    this.now = now;
    this.schedule = schedule;
    this.cancel = cancel;
    this.paused = undefined;
    this.pausedDuration = 0;
    this.pausedAt = null;
    this.wakeups = new Set();
  }

  setPaused(paused) {
    if (paused === this.paused) return;
    const now = this.now();
    if (paused) this.pausedAt = now;
    else if (this.pausedAt !== null) {
      this.pausedDuration += now - this.pausedAt;
      this.pausedAt = null;
    }
    this.paused = paused;
    this.wake();
  }

  get activeTime() {
    return (this.pausedAt ?? this.now()) - this.pausedDuration;
  }

  wake() {
    for (const wake of this.wakeups) wake();
  }

  async wait(duration, progress = () => {}) {
    const deadline = this.activeTime + duration;
    this.check();
    while (this.paused || this.activeTime < deadline) {
      await this.waitForChange(this.paused ? undefined : Math.min(25, deadline - this.activeTime));
      this.check();
      if (!this.paused) progress(this.activeTime);
    }
  }

  async until(predicate, playback = false, progress = () => {}) {
    const time = () => (playback ? this.activeTime : this.now());
    const deadline = time() + 15000;
    while (true) {
      if (playback) await this.wait(0, progress);
      this.check();
      if (predicate()) return;
      if (time() > deadline) throw new Error("The demo response timed out.");
      if (playback) await this.wait(50, progress);
      else await this.waitForChange(50);
    }
  }

  waitForChange(delay) {
    return new Promise((resolve) => {
      let timer;
      const wake = () => {
        this.cancel(timer);
        this.wakeups.delete(wake);
        resolve();
      };
      this.wakeups.add(wake);
      if (delay !== undefined) timer = this.schedule(wake, delay);
    });
  }
}
