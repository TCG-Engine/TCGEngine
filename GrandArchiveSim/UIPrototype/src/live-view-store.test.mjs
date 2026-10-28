import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createLiveViewStore } from './live-view-store.mjs';
const match = revision => ({revision, viewerSeat: 1, players: []});
const tick = () => new Promise(resolve => setImmediate(resolve));
function setup(request) {
 const tasks = new Map(); let id = 0;
 const store = createLiveViewStore({request, schedule(fn, ms) {tasks.set(++id, {fn, ms}); return id;}, cancel(id) {tasks.delete(id);}});
 return {store, poll() { const [key, task] = [...tasks].find(([, task]) => task.ms === 2000); tasks.delete(key); task.fn(); }, tasks};
}
test('polls sequentially and accepts unchanged revisions; disconnect clears and stops', async () => {
 let calls = 0;
 const {store, poll, tasks} = setup(async () => {calls++; return match(3);});
 store.connect({authKey: 'private'}); await tick();
 assert.equal(store.getSnapshot().status, 'connected'); poll(); await tick();
 assert.equal(calls, 2); assert.equal(store.getSnapshot().match.revision, 3);
 assert.ok(!JSON.stringify(store.getSnapshot()).includes('private'));
 store.disconnect(); assert.equal(store.getSnapshot().match, null); assert.equal(tasks.size, 0);
});
test('failed polls clear private data and require manual reconnect', async () => {
 let calls = 0;
 const {store, poll, tasks} = setup(async () => {if (++calls > 1) throw new Error('403'); return match(3);});
 store.connect({}); await tick(); poll(); await tick();
 assert.deepEqual(store.getSnapshot(), {status: 'error', match: null}); assert.equal(tasks.size, 0);
});
test('old response cannot restore private cards after disconnect or replace another seat', async () => {
 const resolves = [];
 const {store} = setup(() => new Promise(resolve => resolves.push(resolve)));
 store.connect({playerID: 1}); store.disconnect();
 resolves[0](match(9)); await tick(); assert.equal(store.getSnapshot().match, null);
 store.connect({playerID: 1}); store.connect({playerID: 2});
 resolves[2]({...match(2), viewerSeat: 2}); await tick();
 resolves[1](match(10)); await tick(); assert.equal(store.getSnapshot().match.viewerSeat, 2);
 store.disconnect();
});
test('older and malformed projections clear board', async () => {
 for (const bad of [match(1), {...match(3), players: null}]) {
  let calls = 0; const {store, poll} = setup(async () => ++calls === 1 ? match(2) : bad);
  store.connect({}); await tick(); poll(); await tick();
  assert.deepEqual(store.getSnapshot(), {status: 'error', match: null});
 }
});
