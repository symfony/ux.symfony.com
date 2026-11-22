import { test } from "node:test";
import assert from "node:assert/strict";
import { ChunkCounter } from "../../assets/demos/upload/chunk_counter.js";

test("counts the chunks sent from this page", () => {
  const counter = new ChunkCounter();

  counter.record(16);
  counter.record(16);

  assert.equal(counter.progressLabel(), "2 of 16 chunks sent from this page");
});

test("reports the chunks the server already held", () => {
  const counter = new ChunkCounter();
  for (let i = 0; i < 6; i++) counter.record(16);

  const label = counter.summaryLabel();

  assert.equal(label, "Resumed: 10 of 16 chunks were already on the server.");
});

test("reports a full transfer", () => {
  const counter = new ChunkCounter();
  for (let i = 0; i < 4; i++) counter.record(4);

  const label = counter.summaryLabel();

  assert.equal(label, "All 4 chunks sent. Reload during the next transfer to see a resume.");
});

test("has no summary before any chunk", () => {
  const counter = new ChunkCounter();

  const label = counter.summaryLabel();

  assert.equal(label, null);
});

test("a new transfer starts the count over", () => {
  const counter = new ChunkCounter();
  for (let i = 0; i < 16; i++) counter.record(16);

  counter.reset();
  counter.record(16);

  assert.equal(counter.progressLabel(), "1 of 16 chunks sent from this page");
  assert.equal(counter.summaryLabel(), "Resumed: 15 of 16 chunks were already on the server.");
});
