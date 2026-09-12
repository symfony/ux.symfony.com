import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { randomUUID } from "node:crypto";
import vm from "node:vm";

const hostSource = readFileSync(
  new URL("../../assets/controllers/inspector-mockup-controller.js", import.meta.url),
  "utf8",
)
  .replace(/^import .*;\n/gm, "")
  .replace("export default class", "globalThis.Host = class");
const scrollSource = readFileSync(
  new URL("../../assets/demos/inspector/scroll.js", import.meta.url),
  "utf8",
).replace("export function", "function");

function fixture(reducedMotion = true) {
  const timers = new Map();
  let timerId = 0;
  const context = vm.createContext({
    Controller: class {},
    AbortController,
    URL,
    crypto: { randomUUID },
    location: { origin: "https://demo.example" },
    window: Object.assign(new EventTarget(), { matchMedia: () => ({ matches: reducedMotion }) }),
    document: Object.assign(new EventTarget(), { hidden: false }),
    IntersectionObserver: class {
      observe() {}
      disconnect() {}
    },
    ResizeObserver: class {
      observe() {}
      disconnect() {}
    },
    getComputedStyle: () => ({ getPropertyValue: () => "1150" }),
    setTimeout: (callback) => {
      timers.set(++timerId, callback);
      return timerId;
    },
    clearTimeout: (id) => timers.delete(id),
  });
  vm.runInContext(hostSource, context);
  const stage = {
    frames: [],
    dataset: {},
    attributes: {},
    clientWidth: 920,
    setAttribute(key, value) {
      this.attributes[key] = value;
    },
    append(frame) {
      this.frames.push(frame);
    },
  };
  class Frame extends EventTarget {
    constructor() {
      super();
      this.style = {};
      this.dataset = {};
      this.attributes = new Map([["data-inspector-mockup-target", "frame"]]);
      this.messages = [];
      this.contentWindow = { postMessage: (data) => this.messages.push(data) };
    }
    animate(keyframes, options) {
      let finish, reject;
      this.animation = {
        keyframes,
        options,
        finished: new Promise((resolve, fail) => {
          finish = resolve;
          reject = fail;
        }),
        finish: () => finish(),
        cancel: () => reject(new DOMException("Cancelled", "AbortError")),
      };
      return this.animation;
    }
    cloneNode() {
      return new Frame();
    }
    removeAttribute(name) {
      this.attributes.delete(name);
    }
    setAttribute(name, value) {
      this.attributes.set(name, value);
    }
    remove() {
      stage.frames = stage.frames.filter((frame) => frame !== this);
    }
  }
  stage.append(new Frame());
  const host = new context.Host();
  Object.defineProperty(host, "frameTarget", {
    get: () => stage.frames.find((frame) => frame.attributes.has("data-inspector-mockup-target")),
  });
  host.stageTarget = stage;
  host.errorTarget = { hidden: true };
  host.shieldTarget = { hidden: false };
  host.interactionTarget = {
    attributes: {},
    setAttribute(name, value) {
      this.attributes[name] = value;
    },
  };
  host.element = { style: { setProperty() {} } };
  host.modeValue = "find";
  host.tabTargets = ["find", "inspect", "connect", "trace"].map((mode) => ({
    dataset: { mode, url: `/demos/inspector/${mode}` },
  }));
  host.visible = true;
  host.connect();
  const reply = (frame, data = {}) =>
    host.receive({
      origin: context.location.origin,
      source: frame.contentWindow,
      data: { inspectorDemo: true, run: host.runId, scenario: host.modeValue, ...data },
    });
  return { host, stage, timers, reply };
}

test("keeps the old document until the prepared frame is ready, without moving the new iframe", () => {
  const { host, stage, timers, reply } = fixture();
  const previous = host.frameTarget;
  const pending = host.pendingFrame;
  assert.equal(pending.style.visibility, "hidden");
  assert.equal(stage.frames.length, 2);
  reply(pending, { ready: true });
  assert.deepEqual(stage.frames, [pending]);
  assert.equal(host.frameTarget, pending);
  assert.equal(pending.style.visibility, "");
  assert.equal(previous.messages[0].command, "stop");
  assert.equal(pending.messages.at(-1).command, "play");
  assert.equal(timers.size, 0);
});

test("rapid changes cancel the old load and ignore its ready message", () => {
  const { host, stage, reply } = fixture();
  const stale = host.pendingFrame;
  const staleRun = host.runId;
  const loading = host.loading;
  host.modeValue = "inspect";
  host.load();
  assert.equal(loading.signal.aborted, true);
  assert.equal(stage.frames.includes(stale), false);
  const pending = host.pendingFrame;
  reply(stale, { ready: true, run: staleRun, scenario: "find" });
  assert.equal(host.pendingFrame, pending);
  reply(pending, { ready: true });
  assert.equal(host.frameTarget, pending);
  assert.equal(stage.frames.length, 1);
});

test("timeout removes the pending frame, hides the wrong view, and supports retry", () => {
  const { host, stage, timers, reply } = fixture();
  const stale = host.pendingFrame;
  const staleRun = host.runId;
  [...timers.values()][0]();
  assert.equal(host.pendingFrame, null);
  assert.equal(stage.frames.length, 1);
  assert.equal(host.frameTarget.style.visibility, "hidden");
  assert.equal(host.errorTarget.hidden, false);
  assert.equal(stage.attributes["aria-busy"], "false");
  reply(stale, { ready: true, run: staleRun });
  assert.equal(host.ready, false);
  host.load();
  assert.equal(host.errorTarget.hidden, true);
  const retry = host.pendingFrame;
  reply(retry, { ready: true });
  assert.equal(host.frameTarget.style.visibility, "");
  assert.equal(host.ready, true);
});

test("driver errors stop a playing scenario and expose recovery", () => {
  const { host, reply } = fixture();
  reply(host.pendingFrame, { ready: true });
  const frame = host.frameTarget;
  reply(frame, { failed: true });
  assert.equal(frame.messages.at(-1).command, "stop");
  assert.equal(host.ready, false);
  assert.equal(host.errorTarget.hidden, false);
});

test("disconnect cancels the pending load and rejects later messages", () => {
  const { host, stage, timers, reply } = fixture();
  const pending = host.pendingFrame;
  const run = host.runId;
  const loading = host.loading;
  host.disconnect();
  assert.equal(loading.signal.aborted, true);
  assert.equal(timers.size, 0);
  assert.equal(stage.frames.includes(pending), false);
  reply(pending, { ready: true, run });
  assert.equal(host.ready, false);
});

test("messages from other origins cannot start the prepared scenario", () => {
  const { host } = fixture();
  host.receive({
    origin: "https://other.example",
    source: host.pendingFrame.contentWindow,
    data: { inspectorDemo: true, ready: true, run: host.runId, scenario: host.modeValue },
  });
  assert.equal(host.ready, false);
});

test("scripted Inspector scrolling stays inside the iframe and restores the native method after errors", () => {
  const calls = [];
  class Element {
    scrollIntoView(options) {
      calls.push(options);
    }
  }
  const original = Element.prototype.scrollIntoView;
  const view = { Element, parent: {} };
  const context = vm.createContext({});
  vm.runInContext(scrollSource, context);
  assert.throws(
    () =>
      context.withDemoScrolling(() => {
        new Element().scrollIntoView({ block: "center", behavior: "smooth" });
        throw new Error("failed action");
      }, view),
    /failed action/,
  );
  assert.equal(calls[0].container, "nearest");
  assert.equal(calls[0].behavior, "instant");
  assert.equal(calls[0].block, "center");
  assert.equal(Element.prototype.scrollIntoView, original);
  view.parent = view;
  context.withDemoScrolling(() => new Element().scrollIntoView(true), view);
  assert.equal(calls[1], true);
});

test("interactive mode unlocks pointer and keyboard access and cannot resume on visibility changes", () => {
  const { host, reply } = fixture();
  reply(host.pendingFrame, { ready: true });
  const frame = host.frameTarget;
  assert.equal(host.interactionTarget.attributes["aria-pressed"], "true");
  assert.equal(host.interactionTarget.title, "Unlock to try the demo");
  host.toggleInteraction();
  assert.equal(host.shieldTarget.hidden, true);
  assert.equal(frame.inert, false);
  assert.equal(frame.tabIndex, 0);
  assert.equal(host.interactionTarget.attributes["aria-pressed"], "false");
  assert.equal(host.interactionTarget.title, "Lock and restart demo");
  assert.equal(frame.messages.at(-1).command, "interact");
  const count = frame.messages.length;
  host.playback();
  assert.equal(frame.messages.length, count);
  host.toggleInteraction();
  assert.equal(host.shieldTarget.hidden, false);
  assert.equal(frame.inert, true);
  assert.equal(frame.tabIndex, -1);
  assert.equal(host.interactionTarget.attributes["aria-pressed"], "true");
  assert.equal(host.interactionTarget.title, "Unlock to try the demo");
  assert.ok(host.pendingFrame);
  reply(host.pendingFrame, { ready: true });
  assert.equal(host.frameTarget.messages.at(-1).command, "play");
});

test("interactive mode selected during loading also applies to later scenario frames", () => {
  const { host, reply } = fixture();
  host.toggleInteraction();
  reply(host.pendingFrame, { ready: true });
  assert.equal(host.frameTarget.messages.at(-1).command, "interact");
  assert.equal(host.frameTarget.inert, false);
  host.modeValue = "inspect";
  host.load();
  reply(host.pendingFrame, { ready: true });
  assert.equal(host.frameTarget.inert, false);
  assert.equal(host.frameTarget.tabIndex, 0);
  assert.equal(host.frameTarget.messages.at(-1).command, "interact");
});

test("crossfades a prepared scenario over the previous document and releases it when finished", async () => {
  const { host, stage, reply } = fixture(false);
  reply(host.pendingFrame, { ready: true });
  const previous = host.frameTarget;
  assert.equal(previous.animation, undefined);
  host.modeValue = "trace";
  host.load();
  const next = host.pendingFrame;
  reply(next, { ready: true });
  assert.equal(host.frameTarget, next);
  assert.equal(stage.frames.length, 2);
  assert.equal(previous.inert, true);
  assert.equal(next.animation.options.duration, 220);
  next.animation.finish();
  await next.animation.finished;
  assert.deepEqual(stage.frames, [next]);
});

test("switching again during a fade removes the outgoing document without losing the current frame", async () => {
  const { host, stage, reply } = fixture(false);
  reply(host.pendingFrame, { ready: true });
  const previous = host.frameTarget;
  host.modeValue = "trace";
  host.load();
  reply(host.pendingFrame, { ready: true });
  const current = host.frameTarget;
  host.modeValue = "inspect";
  host.load();
  await Promise.resolve();
  assert.equal(stage.frames.includes(previous), false);
  assert.equal(stage.frames.includes(current), true);
  assert.equal(stage.frames.length, 2);
  reply(host.pendingFrame, { ready: true });
  host.disconnect();
  await Promise.resolve();
  assert.equal(stage.frames.length, 1);
});

test("a new scenario stops the active run before loading and cannot resume it", () => {
  const { host, reply } = fixture();
  reply(host.pendingFrame, { ready: true });
  const active = host.frameTarget;
  const run = host.runId;
  host.select({ params: { mode: "inspect" } });
  assert.equal(active.messages.at(-1).command, "stop");
  assert.equal(active.messages.at(-1).run, run);
  host.load();
  const pending = host.pendingFrame;
  host.playback();
  assert.equal(active.messages.at(-1).command, "stop");
  assert.equal(pending.messages.length, 0);
  reply(active, { ready: true, run, scenario: "find" });
  assert.equal(host.frameTarget, active);
  reply(pending, { ready: true });
  assert.equal(pending.messages.at(-1).command, "play");
  assert.notEqual(pending.messages.at(-1).run, run);
});

test("rapid switches stop both the displayed and pending frame with their own run IDs", () => {
  const { host, reply } = fixture();
  reply(host.pendingFrame, { ready: true });
  const active = host.frameTarget;
  const activeRun = host.runId;
  host.modeValue = "inspect";
  host.load();
  const pending = host.pendingFrame;
  const pendingRun = host.runId;
  host.modeValue = "trace";
  host.load();
  assert.equal(active.messages.at(-1).command, "stop");
  assert.equal(active.messages.at(-1).run, activeRun);
  assert.equal(pending.messages.at(-1).command, "stop");
  assert.equal(pending.messages.at(-1).run, pendingRun);
});

test("a duplicate ready message cannot restart a paused scenario", () => {
  const { host, reply } = fixture();
  reply(host.pendingFrame, { ready: true });
  const frame = host.frameTarget;
  host.visible = false;
  host.playback();
  const count = frame.messages.length;
  reply(frame, { ready: true });
  assert.equal(frame.messages.length, count);
  assert.equal(frame.messages.at(-1).command, "pause");
});

test("Home on the current first tab starts a fresh run rather than leaving it stopped", () => {
  const { host, reply } = fixture();
  reply(host.pendingFrame, { ready: true });
  const previousRun = host.runId;
  host.tabTargets[0].focus = () => {};
  host.move({ key: "Home", preventDefault() {} });
  assert.ok(host.pendingFrame);
  assert.notEqual(host.runId, previousRun);
  reply(host.pendingFrame, { ready: true });
  assert.equal(host.frameTarget.messages.at(-1).command, "play");
});
