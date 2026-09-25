import { test } from "node:test";
import assert from "node:assert/strict";
import { PlaybackClock } from "../../assets/demos/inspector/playback.js";

function fixture() {
  const timers = new Map();
  let timerId = 0;
  let now = 0;
  let stopped = false;
  const clock = new PlaybackClock(
    () => {
      if (stopped) throw new Error("Playback stopped.");
    },
    {
      now: () => now,
      schedule: (callback, delay) => {
        const id = ++timerId;
        timers.set(id, { callback, at: now + delay });
        return id;
      },
      cancel: (id) => timers.delete(id),
    },
  );
  clock.setPaused(false);

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

  return {
    clock,
    flush,
    stop: () => {
      stopped = true;
      clock.wake();
    },
    tick,
    timers,
  };
}

test("paused time does not advance playback", async () => {
  const { clock, tick } = fixture();
  await tick(400);
  assert.equal(clock.activeTime, 400);
  clock.setPaused(true);
  await tick(30000);
  assert.equal(clock.activeTime, 400);
  clock.setPaused(false);
  await tick(600);
  assert.equal(clock.activeTime, 1000);
});

test("an active wait releases its timer while paused", async () => {
  const { clock, tick, timers } = fixture();
  const progress = [];
  const waiting = clock.wait(1000, (time) => progress.push(time));
  await tick(400);
  clock.setPaused(true);
  assert.equal(timers.size, 0);
  const count = progress.length;
  await tick(30000);
  assert.equal(progress.length, count);
  clock.setPaused(false);
  await tick(600);
  await waiting;
  assert.equal(clock.activeTime, 1000);
});

test("stopping wakes and rejects a paused wait", async () => {
  const { clock, flush, stop, timers } = fixture();
  clock.setPaused(true);
  const waiting = assert.rejects(clock.wait(1000), /Playback stopped/);
  await flush();
  stop();
  await waiting;
  assert.equal(timers.size, 0);
});

test("predicate timeouts use active playback time", async () => {
  const { clock, tick } = fixture();
  const waiting = assert.rejects(clock.until(() => false, true), /timed out/);
  await tick(15100);
  await waiting;
});
