import { Controller } from "@hotwired/stimulus";
import { connectStimulus } from "../demos/inspector/ux-inspector.js";
import { getComponent } from "@symfony/ux-live-component";
import { withDemoScrolling } from "../demos/inspector/scroll.js";

const CHARACTER_DELAY = 500;

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = { scenario: String, panelWidth: { type: Number, default: 380 } };

  connect() {
    this.inspector = document.querySelector("ux-inspector");
    connectStimulus(this.application);
    this.lifetime = new AbortController();
    this.wakeups = new Set();
    this.started = false;
    this.stopped = false;
    this.ready = false;
    this.paused = undefined;
    this.runId = new URL(location.href).searchParams.get("run") ?? "";
    this.pausedDuration = 0;
    this.pausedAt = null;
    this.setPaused(window.parent !== window);
    window.addEventListener(
      "message",
      (event) => {
        if (
          this.stopped ||
          event.origin !== location.origin ||
          event.source !== window.parent ||
          event.data?.run !== this.runId
        )
          return;
        if (event.data.command === "play") {
          this.setPaused(Boolean(event.data.paused));
          this.play();
        } else if (event.data.command === "interact" || event.data.command === "stop") this.stop();
        else if (event.data.command === "pause") this.setPaused(true);
        else if (event.data.command === "resume") this.setPaused(false);
        else if (event.data.command === "ready" && this.ready) this.post({ ready: true });
      },
      { signal: this.lifetime.signal },
    );
    this.prepare().catch((error) => this.fail(error));
  }

  disconnect() {
    this.lifetime.abort();
    this.stop();
  }

  stop() {
    this.stopped = true;
    this.setPaused(true);
    for (const wake of this.wakeups) wake();
    clearTimeout(this.pointerTimer);
    this.pointer?.remove();
    this.shortcut?.remove();
  }

  async prepare() {
    await customElements.whenDefined("ux-inspector");
    await this.until(
      () =>
        this.inspector.hasAttribute("ready") &&
        [...document.querySelectorAll(".result[data-controller]")].every((row) =>
          this.application.getControllerForElementAndIdentifier(row, row.dataset.controller),
        ),
    );
    await Promise.all(
      [...document.querySelectorAll('[data-controller~="live"]')].map(getComponent),
    );
    this.checkConnected();
    this.inspector.setPanelWidth(this.panelWidthValue);
    // Let the dock and its opening transition settle before the parent reveals it.
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    const panel = this.inspector.shadowRoot.querySelector(".inspector");
    await Promise.all(panel.getAnimations().map((animation) => animation.finished.catch(() => {})));
    this.checkConnected();
    this.ready = true;
    this.post({ ready: true });
    if (window.parent === window) this.play();
  }

  post(data) {
    window.parent.postMessage(
      { inspectorDemo: true, scenario: this.scenarioValue, run: this.runId, ...data },
      location.origin,
    );
  }

  async play() {
    if (!this.ready || this.started || this.stopped) return;
    this.started = true;
    try {
      const steps = this.scenarios[this.scenarioValue];
      const queries = new Map();
      this.duration = steps.reduce((duration, step) => {
        if (step.key === "u") return duration + CHARACTER_DELAY;
        if (step.text === undefined) return duration + 1000;
        const input = step.target();
        const previous = queries.get(input) ?? input.value;
        queries.set(input, step.text);
        return duration + 1000 + (previous.length + step.text.length) * CHARACTER_DELAY;
      }, 2000);
      this.startedAt = this.activeTime;
      let nextActionAt = this.startedAt + 1000;
      for (const step of steps) {
        await this.wait(Math.max(0, nextActionAt - this.activeTime));
        nextActionAt = this.activeTime + (step.key === "u" ? CHARACTER_DELAY : 1000);
        if (step.key) {
          await this.pressKey(step.key);
        } else {
          const target = step.target();
          withDemoScrolling(() => this.click(target));
        }
        if (step.text !== undefined) {
          await this.type(step.target(), step.text);
          nextActionAt = this.activeTime + 1000;
        }
        if (step.until) await this.until(step.until, true);
        await this.settleLayout();
      }
      await this.wait(2000);
      await this.settleLayout();
      this.post({ progress: 1, finished: true });
    } catch (error) {
      this.fail(error);
    }
  }

  get scenarios() {
    const page = (selector) => () => document.querySelector(selector);
    const panel = (selector) => () => this.inspector.shadowRoot.querySelector(selector);
    const relation = (name) => () =>
      [...this.inspector.shadowRoot.querySelectorAll(".relation")].find((button) =>
        button.textContent.includes(name),
      );
    const favorite = (id) => ({ target: page(`#product-${id} .favorite`) });
    const remove = (id, count) => ({
      target: page(`#product-${id} .delete`),
      until: () =>
        !document.querySelector(`#product-${id}`) &&
        document.querySelector('[data-demo="cart"]').dataset.demoCount === String(count),
    });
    const delivery = {
      target: page('[data-demo="delivery-toggle"]'),
      until: () => document.querySelector('[data-demo="total"]').dataset.demoTotal === "132",
    };
    const inspect = { target: panel('[data-action="target"]') };
    const componentQuery = (text) => ({
      target: panel('input[aria-label="Find a component"]'),
      text,
    });
    const activityQuery = (text) => ({
      target: panel('input[aria-label="Filter activity"]'),
      text,
    });
    return {
      find: [
        { key: "u" },
        { key: "x", until: () => this.inspector.isOpen },
        { target: panel('[data-action="overlay"]') },
        { target: panel('.filters [data-framework="stimulus"]') },
        componentQuery("ca"),
      ],
      inspect: [
        inspect,
        { target: page("#product-2 .fake-thumb") },
        { target: panel('.target-pill[aria-label="favorite: show element on page"]') },
        favorite(2),
        { target: panel('.target-pill[aria-label="remove(): show element on page"]') },
        favorite(2),
      ],
      connect: [
        delivery,
        inspect,
        { target: page("#product-1 .fake-thumb") },
        { target: relation("InspectorCart") },
        { target: relation("InspectorTotal") },
        {
          target: page('[data-demo="delivery-toggle"]'),
          until: () => document.querySelector('[data-demo="total"]').dataset.demoTotal === "120",
        },
      ],
      trace: [
        favorite(1),
        delivery,
        remove(4, 3),
        { target: panel('[data-action="activity"]') },
        activityQuery("live"),
        { target: panel('#panel-activity button[data-control="disclosure"]') },
        activityQuery(""),
        activityQuery("turbo"),
        { target: panel('#panel-activity button[data-control="disclosure"]') },
        activityQuery(""),
      ],
    };
  }

  click(element) {
    if (!element?.isConnected) throw new Error("The next demo control is unavailable.");
    element.scrollIntoView({ block: "nearest", inline: "nearest" });
    const rect = element.getBoundingClientRect();
    clearTimeout(this.pointerTimer);
    this.pointer?.remove();
    const pointer = document.createElement("span");
    pointer.className = "demo-pointer";
    pointer.popover = "manual";
    pointer.innerHTML =
      '<svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="18" vector-effect="non-scaling-stroke"/></svg>';
    pointer.setAttribute("aria-hidden", "true");
    pointer.style.left = `${rect.left + rect.width / 2}px`;
    pointer.style.top = `${rect.top + rect.height / 2}px`;
    document.body.append(pointer);
    pointer.showPopover();
    this.pointer = pointer;
    // Every scenario action uses the same click path, including the Inspector picker.
    element.click();
    pointer.addEventListener("animationend", () => pointer.remove(), { once: true });
    this.pointerTimer = setTimeout(() => pointer.remove(), 1000);
  }

  async pressKey(key) {
    if (!this.shortcut?.isConnected) {
      this.shortcut = document.createElement("div");
      this.shortcut.className = "demo-shortcut";
      this.shortcut.popover = "manual";
      this.shortcut.setAttribute("aria-hidden", "true");
      this.shortcut.innerHTML = '<kbd data-key="u">U</kbd><kbd data-key="x">X</kbd>';
      document.body.append(this.shortcut);
      this.shortcut.showPopover();
    }
    for (const keycap of this.shortcut.querySelectorAll("kbd")) {
      keycap.classList.toggle("is-pressed", keycap.dataset.key === key);
    }
    document.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true }));
    document.dispatchEvent(new KeyboardEvent("keyup", { key, bubbles: true }));
    if (key === "x") {
      await this.wait(CHARACTER_DELAY);
      this.shortcut.remove();
    }
  }

  async type(input, text) {
    input.focus({ preventScroll: true });
    while (input.value.length) {
      await this.wait(CHARACTER_DELAY);
      input.value = input.value.slice(0, -1);
      input.dispatchEvent(
        new InputEvent("input", { bubbles: true, inputType: "deleteContentBackward" }),
      );
    }
    for (const character of text) {
      await this.wait(CHARACTER_DELAY);
      input.value += character;
      input.dispatchEvent(
        new InputEvent("input", { bubbles: true, data: character, inputType: "insertText" }),
      );
    }
  }

  async settleLayout() {
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    await this.wait(0);
    // Stream removals and nested Live renders can move neighboring overlays.
    this.inspector.setPanelWidth(this.panelWidthValue);
  }

  setPaused(paused) {
    if (paused === this.paused || (this.stopped && !paused)) return;
    const now = performance.now();
    if (paused) this.pausedAt = now;
    else if (this.pausedAt !== null) {
      this.pausedDuration += now - this.pausedAt;
      this.pausedAt = null;
    }
    this.paused = paused;
    for (const wake of this.wakeups) wake();
  }

  get activeTime() {
    return (this.pausedAt ?? performance.now()) - this.pausedDuration;
  }

  waitForChange(delay) {
    return new Promise((resolve) => {
      let timer;
      const wake = () => {
        clearTimeout(timer);
        this.wakeups.delete(wake);
        resolve();
      };
      this.wakeups.add(wake);
      if (delay !== undefined) timer = setTimeout(wake, delay);
    });
  }

  async wait(duration) {
    const deadline = this.activeTime + duration;
    this.checkConnected();
    while (this.paused || this.activeTime < deadline) {
      await this.waitForChange(this.paused ? undefined : Math.min(25, deadline - this.activeTime));
      this.checkConnected();
      if (!this.paused)
        this.post({ progress: Math.min(1, (this.activeTime - this.startedAt) / this.duration) });
    }
  }

  async until(predicate, playback = false) {
    const time = () => (playback ? this.activeTime : performance.now());
    const deadline = time() + 15000;
    while (true) {
      if (playback) await this.wait(0);
      this.checkConnected();
      if (predicate()) return;
      if (time() > deadline) throw new Error("The demo response timed out.");
      if (playback) await this.wait(50);
      else await this.waitForChange(50);
    }
  }

  checkConnected() {
    if (this.stopped || this.lifetime.signal.aborted || !this.element.isConnected)
      throw new DOMException("Demo stopped.", "AbortError");
  }

  fail(error) {
    if (error.name === "AbortError") return;
    this.stop();
    this.post({ failed: true });
    console.error("Inspector demo:", error);
  }
}
