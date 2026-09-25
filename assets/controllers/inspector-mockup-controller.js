import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["stage", "tab", "frame", "error"];
  static values = {
    mode: { type: String, default: "find" },
    autoplay: { type: Boolean, default: true },
  };

  connect() {
    this.modes = this.tabTargets.map((tab) => tab.dataset.mode);
    this.lifetime = new AbortController();
    window.addEventListener("message", (event) => this.receive(event), {
      signal: this.lifetime.signal,
    });
    document.addEventListener("visibilitychange", () => this.playback(), {
      signal: this.lifetime.signal,
    });
    this.observer = new IntersectionObserver(
      ([entry]) => {
        this.visible = entry.isIntersecting;
        this.playback();
      },
      { threshold: 0.25 },
    );
    this.observer.observe(this.element);
    this.resizer = new ResizeObserver(() => this.fit());
    this.resizer.observe(this.stageTarget);
    this.fit();
    this.connected = true;
    if (this.frameTarget.dataset.run) this.adoptInitialFrame();
    else this.load();
  }

  receive(event) {
    const data = event.data;
    if (
      !this.runId ||
      event.origin !== location.origin ||
      event.source !== (this.pendingFrame ?? this.frameTarget).contentWindow ||
      !data?.inspectorDemo ||
      data.run !== this.runId ||
      data.scenario !== this.modeValue
    )
      return;
    if (data.failed) return this.fail();
    if (data.ready && !this.ready) {
      clearTimeout(this.loadTimeout);
      this.loading?.abort();
      if (this.pendingFrame) this.presentPendingFrame(this.pendingFrame);
      this.stageTarget.setAttribute("aria-busy", "false");
      this.ready = true;
      this.post({ command: "play", paused: !this.visible || document.hidden });
    }
    if (typeof data.progress === "number") {
      this.progress = Math.max(this.progress ?? 0, data.progress);
      this.element.style.setProperty("--progress", String(this.progress));
    }
    if (data.finished) this.scheduleNext();
  }

  cancelLoad() {
    this.fade?.cancel();
    this.fade = null;
    this.outgoingFrame?.remove();
    this.outgoingFrame = null;
    clearTimeout(this.loadTimeout);
    this.loading?.abort();
    this.pendingFrame?.remove();
    this.pendingFrame = null;
  }

  fail() {
    this.stopFrames();
    this.cancelLoad();
    clearTimeout(this.autoplayTimer);
    this.runId = null;
    this.ready = false;
    this.frameTarget.style.visibility = "hidden";
    this.stageTarget.dataset.failed = "true";
    this.stageTarget.setAttribute("aria-busy", "false");
    this.errorTarget.hidden = false;
  }

  disconnect() {
    this.connected = false;
    this.ready = false;
    this.stopFrames();
    this.lifetime.abort();
    this.observer.disconnect();
    this.resizer.disconnect();
    this.cancelLoad();
    this.runId = null;
  }

  select(event) {
    this.stopAutoplay();
    this.selectMode(event.params.mode);
  }

  selectMode(mode) {
    this.stopFrames();
    if (mode === this.modeValue) this.load();
    else this.modeValue = mode;
  }

  move(event) {
    const index = this.modes.indexOf(this.modeValue);
    const next = {
      ArrowRight: (index + 1) % this.modes.length,
      ArrowLeft: (index + this.modes.length - 1) % this.modes.length,
      Home: 0,
      End: this.modes.length - 1,
    }[event.key];
    if (next === undefined) return;
    event.preventDefault();
    this.stopAutoplay();
    this.selectMode(this.modes[next]);
    this.tabTargets[next].focus();
  }

  replay() {
    this.stopAutoplay();
    this.load();
  }

  stopAutoplay() {
    this.autoplayValue = false;
    clearTimeout(this.autoplayTimer);
  }

  scheduleNext() {
    clearTimeout(this.autoplayTimer);
    if (!this.autoplayValue) return;
    this.autoplayTimer = setTimeout(() => {
      const index = this.modes.indexOf(this.modeValue);
      this.selectMode(this.modes[(index + 1) % this.modes.length]);
    }, 1000);
  }

  modeValueChanged() {
    for (const stage of this.stageTargets) {
      stage.dataset.mode = this.modeValue;
      stage.setAttribute("aria-labelledby", `inspector-chapter-${this.modeValue}`);
    }
    for (const tab of this.tabTargets) {
      const current = tab.dataset.mode === this.modeValue;
      tab.classList.toggle("is-current", current);
      tab.setAttribute("aria-selected", String(current));
      tab.tabIndex = current ? 0 : -1;
    }
    if (this.connected) this.load();
  }

  adoptInitialFrame() {
    const frame = this.frameTarget;
    this.ready = false;
    this.runId = frame.dataset.run;
    this.loading = new AbortController();
    this.presented = true;
    this.stageTarget.setAttribute("aria-busy", "true");
    this.errorTarget.hidden = true;
    this.element.style.setProperty("--progress", "0");
    frame.addEventListener("load", () => this.post({ command: "ready" }, frame), {
      signal: this.loading.signal,
      once: true,
    });
    this.loadTimeout = setTimeout(() => this.fail(), 15000);
    this.post({ command: "ready" }, frame);
  }

  load() {
    const selected = this.tabTargets.find((tab) => tab.dataset.mode === this.modeValue);
    this.stopFrames();
    this.ready = false;
    this.cancelLoad();
    this.runId = crypto.randomUUID();
    this.loading = new AbortController();
    this.stageTarget.setAttribute("aria-busy", "true");
    this.errorTarget.hidden = true;
    this.startProgress();
    delete this.stageTarget.dataset.failed;
    const url = new URL(selected.dataset.url, location.origin);
    url.searchParams.set("run", this.runId);
    // Prepare the next document before fading it over the current one.
    const frame = this.frameTarget.cloneNode(false);
    frame.removeAttribute("data-inspector-mockup-target");
    frame.removeAttribute("srcdoc");
    frame.style.visibility = "hidden";
    frame.loading = "eager";
    frame.dataset.run = this.runId;
    frame.src = url.href;
    this.pendingFrame = frame;
    frame.addEventListener(
      "load",
      () => {
        if (this.pendingFrame !== frame) return;
        this.presentPendingFrame(frame);
        this.post({ command: "ready" });
      },
      { signal: this.loading.signal, once: true },
    );
    this.loadTimeout = setTimeout(() => this.fail(), 15000);
    this.stageTarget.append(frame);
  }

  startProgress() {
    this.progress = 0;
    this.element.toggleAttribute("data-progress-reset", true);
    this.element.style.setProperty("--progress", "0");
    requestAnimationFrame(() => {
      if (!this.connected) return;
      this.element.removeAttribute("data-progress-reset");
      requestAnimationFrame(() => {
        if (!this.connected) return;
        this.progress = 0.015;
        this.element.style.setProperty("--progress", String(this.progress));
      });
    });
  }

  presentPendingFrame(frame) {
    if (this.pendingFrame !== frame) return;
    const previous = this.frameTarget;
    this.pendingFrame = null;
    frame.style.visibility = "";
    frame.setAttribute("data-inspector-mockup-target", "frame");
    previous.removeAttribute("data-inspector-mockup-target");
    previous.inert = true;
    if (this.presented && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      this.outgoingFrame = previous;
      this.fade = frame.animate([{ opacity: 0 }, { opacity: 1 }], {
        duration: 220,
        easing: "ease-out",
      });
      this.fade.finished
        .then(() => {
          previous.remove();
          if (this.outgoingFrame === previous) {
            this.outgoingFrame = null;
            this.fade = null;
          }
        })
        .catch(() => {});
    } else previous.remove();
    this.presented = true;
    this.fit();
  }

  stopFrames() {
    this.ready = false;
    this.post({ command: "stop" }, this.frameTarget);
    if (this.pendingFrame) this.post({ command: "stop" }, this.pendingFrame);
  }

  post(data, frame = this.pendingFrame ?? this.frameTarget) {
    frame.contentWindow?.postMessage({ ...data, run: frame.dataset.run }, location.origin);
  }

  playback() {
    if (this.ready)
      this.post({ command: this.visible && !document.hidden ? "resume" : "pause" });
  }

  fit() {
    const width = Number.parseFloat(getComputedStyle(this.stageTarget).getPropertyValue("--w"));
    if (!width) return;
    const transform = `scale(${this.stageTarget.clientWidth / width})`;
    this.frameTarget.style.transform = transform;
    if (this.pendingFrame) this.pendingFrame.style.transform = transform;
  }

  zoom() {
    const shell = this.element.querySelector(".uxc-shell");
    if (document.fullscreenElement) document.exitFullscreen();
    else shell.requestFullscreen?.().catch(() => {});
  }
}
