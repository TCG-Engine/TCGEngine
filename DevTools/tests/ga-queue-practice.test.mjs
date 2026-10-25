import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../../GrandArchiveSim/QueuePractice.js', import.meta.url), 'utf8');
function setup() {
  const nodes = [], requests = [], matches = [], timers = [];
  function element(tag) {
    const node = { tag, style: {}, children: [], setAttribute() {}, focus() {},
      appendChild(child) { this.children.push(child); }, remove() { this.removed = true; } };
    nodes.push(node); return node;
  }
  const context = vm.createContext({
    document: { createElement: element, body: element('body') }, URLSearchParams, AbortController,
    setTimeout(fn) { timers.push(fn); return timers.length; }, clearTimeout() {},
    fetch(url, options) { return new Promise((resolve, reject) => {
      requests.push({ url, options, resolve(value) { resolve({ ok: true, json: async () => value }); }, reject });
    }); }
  });
  vm.runInContext(source, context);
  context.startGAQueuePractice({ lobbyID: 'human-lobby', playerID: 1, authKey: 'human-key' },
    { deckLink: 'deck\nlist', gameType: 'constructed', shareAnonymizedGameplayData: false },
    { base: '/', gameUrl: (seat, game, key) => `/${game}?seat=${seat}&key=${key}`, found: (...args) => matches.push(args) });
  return { nodes, requests, matches, timers, button: text => nodes.find(n => n.textContent === text),
    panel: nodes.find(n => n.id === 'ga-queue-practice') };
}
const flush = () => new Promise(resolve => setImmediate(resolve));
test('practice keeps original queue credentials and hands off with the human key', async () => {
  const s = setup();
  s.button('Practice while waiting').onclick();
  const bot = new URLSearchParams(s.requests[1].options.body);
  assert.equal(bot.get('format'), 'bot');
  assert.equal(bot.get('botPlayers'), '2');
  assert.equal(bot.get('deckLink'), 'deck\nlist');
  assert.equal(bot.get('shareAnonymizedGameplayData'), '0');
  s.requests[1].resolve({ success: true, ready: true, gameName: 'practice', playerID: 1, authKey: 'bot-key' });
  await flush();
  assert.equal(s.nodes.find(n => n.tag === 'iframe').src, '/practice?seat=1&key=bot-key');
  assert.equal(new URLSearchParams(s.requests[0].options.body).get('lobbyID'), 'human-lobby');
  s.requests[0].resolve({ success: true, ready: true, gameName: 'human', playerID: 1 });
  await flush();
  assert.deepEqual(s.matches, [[1, 'human', 'human-key']]);
  assert.equal(s.panel.removed, true);
});
test('late bot creation cannot replace a found human match', async () => {
  const s = setup(); s.button('Practice while waiting').onclick();
  s.requests[0].resolve({ success: true, ready: true, gameName: 'human', playerID: 1 });
  await flush();
  s.requests[1].resolve({ success: true, ready: true, gameName: 'bot', playerID: 1, authKey: 'bot-key' });
  await flush();
  assert.equal(s.nodes.some(n => n.tag === 'iframe'), false);
  assert.equal(s.matches.length, 1);
});
test('cancel stops polling and ignores an in-flight result', async () => {
  const s = setup(); s.button('Cancel queue').onclick();
  assert.equal(new URLSearchParams(s.requests[1].options.body).get('waitingOnly'), '1');
  s.requests[1].resolve({ success: true }); await flush();
  assert.equal(s.requests[0].options.signal.aborted, true);
  s.requests[0].resolve({ success: true, ready: true, gameName: 'stale', playerID: 1 });
  await flush(); assert.equal(s.matches.length, 0);
});
test('failed practice can retry while matchmaking reconnects', async () => {
  const s = setup(); const button = s.button('Practice while waiting'); button.onclick();
  s.requests[1].resolve({ success: false, message: 'Deck unavailable.' });
  s.requests[0].reject(new Error('offline')); await flush();
  assert.equal(button.disabled, false); assert.equal(s.timers.length, 1);
  s.timers[0](); assert.equal(s.requests.length, 3);
});
test('expired queue stops retrying and offers return to menu', async () => {
  const s = setup(); s.requests[0].resolve({ gone: true, message: 'This lobby has ended.' });
  await flush(); assert.equal(s.timers.length, 0);
  s.button('Back to menu').onclick(); assert.equal(s.panel.removed, true);
});

test('cancel refusal leaves the queue active to receive the paired match', async () => {
  const s = setup(); s.button('Cancel queue').onclick();
  s.requests[1].resolve({ success: false, message: 'Your match is being prepared.' }); await flush();
  assert.equal(s.panel.removed, undefined);
  assert.equal(s.button('Cancel queue').disabled, false);
  s.requests[0].resolve({ success: true, ready: true, gameName: 'paired', playerID: 1 });
  await flush(); assert.deepEqual(s.matches, [[1, 'paired', 'human-key']]);
});
