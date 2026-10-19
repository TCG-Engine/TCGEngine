import { test } from 'node:test';
import assert from 'node:assert/strict';
import { DatabaseSync } from 'node:sqlite';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import worker, { digest, revision } from '../worker.mjs';

// Execute the real service SQL against SQLite, including D1's atomic batch semantics.
class D1 {
  constructor() { this.sql = new DatabaseSync(':memory:'); this.sql.exec(readFileSync(new URL('../migrations/0001_card_code.sql', import.meta.url), 'utf8')); }
  prepare(query) {
    const db = this;
    return { args: [], bind(...args) { this.args = args; return this; }, async all() { return { results: db.sql.prepare(query).all(...this.args) }; }, async first() { return db.sql.prepare(query).get(...this.args) ?? null; }, query };
  }
  async batch(statements) {
    this.sql.exec('BEGIN');
    try {
      const results = statements.map(s => ({ results: this.sql.prepare(s.query).all(...s.args) }));
      this.sql.exec('COMMIT'); return results;
    } catch (error) { this.sql.exec('ROLLBACK'); throw error; }
  }
}
async function fixture() {
  const DB = new D1();
  for (const role of ['reader', 'developer', 'maintainer']) DB.sql.prepare('INSERT INTO tokens VALUES (?, ?, ?, ?, ?, 0)').run(await digest(role), role, 'GrandArchiveSim', role, '2099-01-01T00:00:00.000Z');
  const env = { DB, CARD_CODE_ROOT: 'GrandArchiveSim' };
  const call = async (action, body, token = 'developer', root = 'GrandArchiveSim', customHeader = false) => {
    const url = new URL('https://example.test/'); url.searchParams.set('action', action);
    const headers = customHeader ? { 'X-Card-Code-Token': token } : { Authorization: `Bearer ${token}` };
    if (!body) { url.searchParams.set('root', root); url.searchParams.set('card', 'TEST'); }
    const response = await worker.fetch(new Request(url, { method: body ? 'POST' : 'GET', headers, ...(body ? { body: JSON.stringify({ root, ...body }) } : {}) }), env);
    return { status: response.status, ...await response.json() };
  };
  const emptyRevision = await revision([]);
  const save = async (code, baseRevision = emptyRevision, extra = {}) => call('save', { cardId: 'TEST', baseRevision, abilities: [{ macroName: 'Enter', abilityCode: code, isImplemented: true }], ...extra });
  return { DB, call, save };
}

test('PHP-compatible revision includes Unicode, nulls, and line terminators', async () => {
  const rows = [{ card_id: 'A', macro_name: 'Enter', ability_type: 'macro', ability_code: 'é/\u2028\u2029', prereq_code: null, listener_zones: null, ability_name: '卡', is_implemented: 1 }];
  const php = execFileSync('php', ['-r', 'require "../../CardEditor/Database/CardCodeServiceDB.php"; echo CardCodeServiceDB::RevisionForRows(json_decode(stream_get_contents(STDIN), true));'], { input: JSON.stringify(rows), cwd: new URL('..', import.meta.url) }).toString();
  assert.equal(await revision(rows), php);
});
test('authentication, role, root and expiration boundaries', async () => {
  const { DB, call } = await fixture();
  assert.equal((await call('snapshot', null, 'wrong')).status, 403);
  assert.equal((await call('snapshot', null, 'reader', 'OtherSim')).status, 403);
  assert.equal((await call('snapshot', null, 'reader', 'GrandArchiveSim', true)).status, 200);
  assert.equal((await call('ensure-cards', { cardIds: ['A'] }, 'reader')).status, 403);
  assert.equal((await call('checkpoint', {}, 'developer')).status, 403);
  DB.sql.exec("UPDATE tokens SET expires_at = '2000-01-01T00:00:00.000Z'");
  assert.equal((await call('snapshot')).status, 403);
});
test('saves, stale revisions, no-code implemented cards, and listeners', async () => {
  const { call, save } = await fixture();
  const first = await save('return 1;'); assert.equal(first.status, 200);
  assert.equal((await save('return 2;')).status, 409);
  const second = await save('return 2;', first.revision); assert.equal(second.status, 200);
  assert.equal((await call('card')).abilities[0].ability_code, 'return 2;');
  const invalid = await save('x', second.revision, { abilities: [{ macroName: 'Event', abilityCode: 'x', abilityType: 'listener', listenerZones: [] }] });
  assert.equal(invalid.status, 400);
  assert.equal((await call('card')).revision, second.revision);
  const marker = await save('unused', second.revision, { abilities: [], cardImplemented: true });
  assert.equal(marker.abilities[0].ability_name, '[Card Implemented]');
  assert.equal((await call('cards')).cards[0].isImplemented, true);
});
test('daily checkpoints are immutable, restore works, unchanged days are skipped', async () => {
  const { DB, call, save } = await fixture();
  const first = await save('original');
  // Move the initial empty checkpoint to yesterday, then checkpoint authored content today.
  DB.sql.exec("UPDATE checkpoints SET checkpoint_date = '2000-01-01'");
  const cp = await call('checkpoint', {}, 'maintainer'); assert.equal(cp.status, 200);
  const id = cp.checkpoint.id;
  await save('changed', first.revision);
  assert.equal((await call('checkpoint', {}, 'maintainer')).checkpoint.id, id);
  assert.equal((await call('restore', { checkpointId: id }, 'maintainer')).status, 200);
  assert.equal((await call('card')).abilities[0].ability_code, 'original');
  DB.sql.prepare('UPDATE checkpoints SET checkpoint_date = ? WHERE id = ?').run('2000-01-02', id);
  await call('checkpoint', {}, 'maintainer');
  assert.equal(DB.sql.prepare('SELECT count(*) AS n FROM checkpoints').get().n, 2);
});
test('workspace race rolls back checkpoint and writes', async () => {
  const { DB, call, save } = await fixture();
  const realBatch = DB.batch.bind(DB);
  DB.batch = async statements => {
    if (statements[0].query.startsWith('INSERT INTO write_guard')) DB.sql.exec('UPDATE workspace SET version = version + 1');
    return realBatch(statements);
  };
  assert.equal((await save('lost update')).status, 409);
  assert.equal((await call('snapshot')).abilities.length, 0);
  assert.equal(DB.sql.prepare('SELECT count(*) AS n FROM checkpoints').get().n, 0);
});
test('large ensure-cards is atomic and idempotent', async () => {
  const { call } = await fixture();
  const cardIds = Array.from({ length: 1500 }, (_, i) => `CARD_${i}`);
  assert.equal((await call('ensure-cards', { cardIds })).insertedCount, 1500);
  assert.equal((await call('ensure-cards', { cardIds })).insertedCount, 0);
  assert.equal((await call('snapshot')).abilities.length, 1500);
});
test('snapshot ordering matches MySQL for mixed-case Grand Archive IDs', async () => {
  const { call } = await fixture();
  await call('ensure-cards', { cardIds: ['0D6AfZyKXh', '0cnn1eh85y', '0d93t7bfwc'] });
  assert.deepEqual((await call('snapshot')).abilities.map(r => r.card_id), ['0cnn1eh85y', '0D6AfZyKXh', '0d93t7bfwc']);
});
