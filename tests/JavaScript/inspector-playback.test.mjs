import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import vm from "node:vm";

const source = readFileSync(
  new URL("../../assets/controllers/inspector-demo-controller.js", import.meta.url),
  "utf8",
)
  .replace(/^import .*;\n/gm, "")
  .replace("export default class", "globalThis.Driver = class");

function fixture(automatic = true) {
  const timers = new Map();
  let timerId = 0;
  let now = 0;
  const advance = (duration) => {
    now += duration;
  };
  const context = vm.createContext({
    Controller: class {},
    DOMException,
    KeyboardEvent: class extends Event {
      constructor(type, options) {
        super(type, options);
        this.key = options.key;
      }
    },
    InputEvent: class extends Event {
      constructor(type, options) {
        super(type, options);
        this.data = options.data;
        this.inputType = options.inputType;
      }
    },
    performance: { now: () => now },
    setTimeout: (callback, duration) => {
      const id = ++timerId;
      timers.set(id, { callback, at: now + duration });
      if (automatic) {
        advance(duration);
        queueMicrotask(() => {
          if (timers.delete(id)) callback();
        });
      }
      return id;
    },
    clearTimeout: (id) => timers.delete(id),
    withDemoScrolling: (action) => action(),
  });
  vm.runInContext(source, context);
  const driver = new context.Driver();
  driver.lifetime = new AbortController();
  driver.wakeups = new Set();
  driver.element = { isConnected: true };
  driver.ready = true;
  driver.pausedDuration = 0;
  driver.pausedAt = null;
  driver.setPaused(false);
  driver.startedAt = 0;
  driver.duration = 10000;
  driver.post = () => {};
  driver.fail = (error) => {
    if (error.name !== "AbortError") throw error;
  };
  driver.settleLayout = async () => advance(34);
  driver.scenarioValue = "test";
  const actions = [];
  const click = driver.click.bind(driver);
  driver.click = () => actions.push(driver.activeTime);
  const input = new EventTarget();
  input.value = "";
  input.focus = () => {};
  const run = (steps) => {
    Object.defineProperty(driver, "scenarios", { value: { test: steps } });
    return driver.play();
  };
  const flush = async () => {
    for (let i = 0; i < 20; ++i) await Promise.resolve();
  };
  const tick = async (duration) => {
    const end = now + duration;
    await flush();
    while (true) {
      const next = [...timers].sort((a, b) => a[1].at - b[1].at)[0];
      if (!next || next[1].at > end) break;
      now = next[1].at;
      timers.delete(next[0]);
      next[1].callback();
      await flush();
    }
    now = end;
    await flush();
  };
  return { driver, input, advance, actions, run, context, click, timers, tick, flush };
}

test("keeps a one-second pause after half-second keystrokes without adding response or layout delays", async () => {
  const { driver, input, advance, actions, run } = fixture();
  driver.until = async () => advance(275);
  await run([
    { target: () => input, text: "cart" },
    { target: () => input, until: () => true },
    { target: () => input, text: "" },
  ]);
  assert.deepEqual(actions, [1000, 4000, 5000]);
  assert.equal(input.value, "");
});

test("a slow response delays the next action without a burst of catch-up clicks", async () => {
  const { driver, input, advance, actions, run } = fixture();
  driver.until = async () => advance(1400);
  await run([
    { target: () => input, until: () => true },
    { target: () => input },
    { target: () => input },
  ]);
  assert.deepEqual(actions, [1000, 2434, 3434]);
});

test("time spent paused does not advance the action schedule", async () => {
  const { driver, input, advance, actions, run } = fixture();
  driver.setPaused(true);
  advance(5000);
  assert.equal(driver.activeTime, 0);
  driver.setPaused(false);
  await run([{ target: () => input }, { target: () => input }]);
  assert.deepEqual(actions, [1000, 2000]);
});

test("typing and clearing update the filter on each character", async () => {
  const { driver, input } = fixture();
  const changes = [];
  input.addEventListener("input", (event) =>
    changes.push([input.value, event.inputType, driver.activeTime]),
  );
  await driver.type(input, "live");
  await driver.type(input, "");
  assert.deepEqual(changes, [
    ["l", "insertText", 500],
    ["li", "insertText", 1000],
    ["liv", "insertText", 1500],
    ["live", "insertText", 2000],
    ["liv", "deleteContentBackward", 2500],
    ["li", "deleteContentBackward", 3000],
    ["l", "deleteContentBackward", 3500],
    ["", "deleteContentBackward", 4000],
  ]);
});

test("taking manual control stops typing before the next character", async () => {
  const { driver, input } = fixture();
  input.addEventListener("input", () => {
    driver.stop();
  });
  await assert.rejects(driver.type(input, "cart"), { name: "AbortError" });
  assert.equal(input.value, "c");
});

test("click ripples enter the browser top layer before the target action runs", () => {
  const { context, click } = fixture();
  const order = [];
  const pointer = Object.assign(new EventTarget(), {
    style: {},
    setAttribute() {},
    remove() {},
    showPopover() {
      order.push("top layer");
    },
  });
  context.document = {
    createElement: () => pointer,
    body: {
      append() {
        order.push("append");
      },
    },
  };
  click({
    isConnected: true,
    scrollIntoView() {},
    getBoundingClientRect: () => ({ left: 100, top: 200, width: 40, height: 40 }),
    click() {
      order.push("click");
    },
  });
  assert.equal(pointer.popover, "manual");
  assert.equal(pointer.style.left, "120px");
  assert.equal(pointer.style.top, "220px");
  assert.deepEqual(order, ["append", "top layer", "click"]);
});

test("shows U then X as a sequential shortcut before the first click", async () => {
  const { driver, context, input, actions, run } = fixture();
  const keys = [];
  const keycaps = ["u", "x"].map((key) => ({ dataset: { key }, classList: { toggle() {} } }));
  const shortcut = {
    isConnected: false,
    setAttribute() {},
    querySelectorAll: () => keycaps,
    showPopover() {
      this.shown = true;
    },
    remove() {
      this.isConnected = false;
    },
  };
  context.document = Object.assign(new EventTarget(), {
    createElement: () => shortcut,
    body: {
      append(element) {
        element.isConnected = true;
      },
    },
  });
  context.document.addEventListener("keydown", (event) =>
    keys.push([event.key, driver.activeTime, shortcut.shown]),
  );
  await run([{ key: "u" }, { key: "x" }, { target: () => input }]);
  assert.deepEqual(keys, [
    ["u", 1000, true],
    ["x", 1500, true],
  ]);
  assert.deepEqual(actions, [2500]);
  assert.equal(shortcut.isConnected, false);
});

test("pausing an active wait releases its timer and posts nothing until resumed", async () => {
  const { driver, input, actions, run, tick, timers, flush } = fixture(false);
  const progress = [];
  driver.post = (data) => progress.push(data);
  const playing = run([{ target: () => input }]);
  await tick(400);
  driver.setPaused(true);
  await flush();
  const count = progress.length;
  assert.equal(timers.size, 0);
  await tick(30000);
  assert.equal(progress.length, count);
  assert.deepEqual(actions, []);
  driver.setPaused(false);
  await tick(600);
  assert.deepEqual(actions, [1000]);
  await tick(2100);
  await playing;
});

test("stopping during typing cancels timers and prevents resume or a second play", async () => {
  const { driver, input, actions, run, tick, timers, flush } = fixture(false);
  const messages = [];
  driver.post = (data) => messages.push(data);
  const playing = run([{ target: () => input, text: "cart" }, { target: () => input }]);
  await tick(1600);
  assert.equal(input.value, "c");
  driver.stop();
  assert.equal(timers.size, 0);
  await flush();
  const count = messages.length;
  driver.setPaused(false);
  await driver.play();
  await tick(10000);
  await playing;
  assert.equal(input.value, "c");
  assert.deepEqual(actions, [1000]);
  assert.equal(messages.length, count);
  assert.equal(
    messages.some((data) => data.finished),
    false,
  );
});

test("a response arriving while paused waits for resume and excludes paused time from its timeout", async () => {
  const { driver, tick, flush, timers } = fixture(false);
  let response = false;
  let finished = false;
  const pending = driver
    .until(() => response, true)
    .then(() => {
      finished = true;
    });
  await tick(100);
  driver.setPaused(true);
  await flush();
  assert.equal(timers.size, 0);
  await tick(30000);
  response = true;
  await flush();
  assert.equal(finished, false);
  driver.setPaused(false);
  await tick(50);
  await pending;
  assert.equal(finished, true);
});

test("stopping a paused response wait rejects immediately and releases all waits", async () => {
  const { driver, flush, timers } = fixture(false);
  driver.setPaused(true);
  const pending = assert.rejects(
    driver.until(() => false, true),
    { name: "AbortError" },
  );
  await flush();
  driver.stop();
  await pending;
  assert.equal(timers.size, 0);
  assert.equal(driver.wakeups.size, 0);
});

test("disconnect during a scheduled action prevents that action and clears the timer", async () => {
  const { driver, input, run, tick, timers, actions } = fixture(false);
  const playing = run([{ target: () => input }]);
  await tick(500);
  driver.disconnect();
  assert.equal(timers.size, 0);
  await playing;
  await tick(2000);
  assert.deepEqual(actions, []);
});

test("initialization errors reach the same recovery path as action errors", async () => {
  const { driver, run } = fixture();
  const errors = [];
  driver.fail = (error) => errors.push(error);
  await run([{ target: () => null, text: "ca" }]);
  assert.equal(errors.length, 1);
  assert.equal(errors[0].name, "TypeError");
});
