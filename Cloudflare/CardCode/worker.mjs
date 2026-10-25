const columns = ['card_id', 'macro_name', 'ability_type', 'ability_code', 'prereq_code', 'listener_zones', 'ability_name', 'is_implemented'];
// Grand Archive IDs are mixed-case ASCII. Match the source MySQL collation for snapshot hashes.
const selectRows = 'SELECT * FROM card_abilities WHERE root_name = ? ORDER BY card_id COLLATE NOCASE, created_at, id';
const json = (payload, status = 200) => Response.json(payload, { status, headers: { 'Cache-Control': 'no-store', 'X-Content-Type-Options': 'nosniff' } });
class ApiError extends Error { constructor(status, message) { super(message); this.status = status; } }
export async function digest(text) {
  return [...new Uint8Array(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text)))].map(x => x.toString(16).padStart(2, '0')).join('');
}
export async function revision(rows) {
  const canonical = rows.map(row => Object.fromEntries(columns.map(key => [key, row[key] ?? (['prereq_code', 'listener_zones', 'ability_name'].includes(key) ? null : key === 'is_implemented' ? 0 : key === 'ability_type' ? 'macro' : '')])));
  // PHP JSON_UNESCAPED_UNICODE still escapes these two line terminators.
  return digest(JSON.stringify(canonical).replaceAll('\u2028', '\\u2028').replaceAll('\u2029', '\\u2029'));
}
function cardId(value) {
  if (typeof value !== 'string' || !value.trim() || new TextEncoder().encode(value.trim()).length > 128) throw new ApiError(400, 'Invalid card ID');
  return value.trim();
}
function normalize(abilities, implemented) {
  if (!Array.isArray(abilities) || abilities.length > 200) throw new ApiError(400, 'Invalid abilities');
  const rows = abilities.map(a => {
    if (!a || typeof a !== 'object') throw new ApiError(400, 'Invalid ability');
    const row = {};
    for (const [snake, camel] of [['macro_name','macroName'], ['ability_code','abilityCode'], ['ability_type','abilityType'], ['prereq_code','prereqCode'], ['listener_zones','listenerZones'], ['ability_name','abilityName'], ['is_implemented','isImplemented']]) row[snake] = a[camel] ?? a[snake] ?? null;
    if (typeof row.macro_name !== 'string' || !row.macro_name.trim() || typeof row.ability_code !== 'string' || !row.ability_code) throw new ApiError(400, 'Every ability requires a macro and code');
    row.macro_name = row.macro_name.trim();
    row.ability_type = row.ability_type === 'listener' ? 'listener' : 'macro';
    row.listener_zones = row.ability_type === 'listener' ? (Array.isArray(row.listener_zones) ? row.listener_zones.map(String).map(x => x.trim()).filter(Boolean).join(',') : String(row.listener_zones ?? '').trim()) : null;
    if (row.ability_type === 'listener' && !row.listener_zones) throw new ApiError(400, 'Listener abilities require at least one active zone');
    for (const key of ['prereq_code', 'ability_name']) if (row[key] !== null && typeof row[key] !== 'string') throw new ApiError(400, `Invalid ${key}`);
    row.is_implemented = row.is_implemented && row.is_implemented !== '0' ? 1 : 0;
    return row;
  });
  if (!rows.length && implemented) rows.push({ macro_name: '', ability_type: 'macro', ability_code: '', prereq_code: null, listener_zones: null, ability_name: '[Card Implemented]', is_implemented: 1 });
  return rows;
}
async function state(db, root) {
  const results = await db.batch([db.prepare('SELECT version FROM workspace WHERE root = ?').bind(root), db.prepare(selectRows).bind(root)]);
  if (!results[0].results.length) throw new ApiError(404, 'Unknown workspace');
  return { version: results[0].results[0].version, rows: results[1].results };
}
function guard(db, root, version) {
  return [db.prepare('INSERT INTO write_guard(ok) VALUES (COALESCE((SELECT version = ? FROM workspace WHERE root = ?), 0))').bind(version, root), db.prepare('DELETE FROM write_guard')];
}
async function checkpointStatements(db, root, rows, actor, date) {
  const checksum = await revision(rows);
  return [
    db.prepare(`INSERT OR IGNORE INTO checkpoints(root_name, checkpoint_date, payload_sha256, ability_count, created_by)
      SELECT ?, ?, ?, ?, ? WHERE COALESCE((SELECT payload_sha256 FROM checkpoints WHERE root_name = ? ORDER BY checkpoint_date DESC, id DESC LIMIT 1), '') <> ?`).bind(root, date, checksum, rows.length, actor, root, checksum),
    // changes() ties row copies strictly to a newly inserted checkpoint, never an existing day.
    db.prepare(`INSERT INTO checkpoint_abilities(checkpoint_id, ability_id, payload)
      SELECT (SELECT id FROM checkpoints WHERE root_name = ? AND checkpoint_date = ?), id,
      json_object('id',id,'root_name',root_name,'card_id',card_id,'macro_name',macro_name,'ability_type',ability_type,'ability_code',ability_code,'prereq_code',prereq_code,'listener_zones',listener_zones,'ability_name',ability_name,'is_implemented',is_implemented,'created_at',created_at,'updated_at',updated_at)
      FROM card_abilities WHERE root_name = ? AND changes() = 1`).bind(root, date, root),
  ];
}
async function mutate(db, root, current, actor, statements) {
  const date = new Date().toISOString().slice(0, 10);
  try {
    return await db.batch([...guard(db, root, current.version), ...await checkpointStatements(db, root, current.rows, actor, date), ...statements, db.prepare('UPDATE workspace SET version = version + 1 WHERE root = ?').bind(root)]);
  } catch (error) {
    if (String(error.message).includes('CHECK constraint failed')) throw new ApiError(409, 'Workspace changed during this request; reload and reconcile');
    throw error;
  }
}
async function checkpoint(db, root, actor) {
  const current = await state(db, root);
  await mutate(db, root, current, actor, []);
  return db.prepare('SELECT * FROM checkpoints WHERE root_name = ? ORDER BY checkpoint_date DESC, id DESC LIMIT 1').bind(root).first();
}
async function handle(request, env) {
  const url = new URL(request.url);
  if (!['GET', 'POST'].includes(request.method)) throw new ApiError(405, 'Method not allowed');
  let body = {};
  if (request.method === 'POST') {
    const text = await request.text();
    if (new TextEncoder().encode(text).length > 1000000) throw new ApiError(413, 'Request too large');
    try { body = JSON.parse(text); } catch { throw new ApiError(400, 'Invalid JSON'); }
    if (!body || typeof body !== 'object' || Array.isArray(body)) throw new ApiError(400, 'Invalid JSON object');
  }
  const root = url.searchParams.get('root') ?? body.root;
  if (root !== env.CARD_CODE_ROOT) throw new ApiError(403, 'Workspace is not permitted');
  const action = url.searchParams.get('action') ?? body.action;
  const plain = request.headers.get('X-Card-Code-Token') || request.headers.get('Authorization')?.replace(/^Bearer\s+/i, '') || '';
  if (!plain || plain.length > 512) throw new ApiError(403, 'Invalid token');
  const token = await env.DB.prepare('SELECT * FROM tokens WHERE token_hash = ? AND root = ? AND revoked = 0 AND expires_at > ?').bind(await digest(plain), root, new Date().toISOString()).first();
  if (!token) throw new ApiError(403, 'Invalid or expired token');
  const write = ['save', 'ensure-cards'].includes(action);
  const maintain = ['checkpoint', 'restore'].includes(action);
  if ((write && token.role === 'reader') || (maintain && token.role !== 'maintainer')) throw new ApiError(403, 'Token lacks required scope');
  const method = write || maintain ? 'POST' : 'GET';
  if (request.method !== method) throw new ApiError(405, 'Method not allowed');
  const db = env.DB;
  if (action === 'checkpoints') return json({ success: true, root, checkpoints: (await db.prepare('SELECT * FROM checkpoints WHERE root_name = ? ORDER BY checkpoint_date DESC, id DESC').bind(root).all()).results });
  if (action === 'checkpoint') return json({ success: true, checkpoint: await checkpoint(db, root, token.token_name) });
  const current = await state(db, root);
  if (action === 'snapshot') return json({ success: true, root, revision: await revision(current.rows), abilities: current.rows });
  if (action === 'cards') {
    const cards = new Map();
    for (const row of current.rows) cards.set(row.card_id, { cardId: row.card_id, isImplemented: Boolean(row.is_implemented || cards.get(row.card_id)?.isImplemented) });
    return json({ success: true, root, cards: [...cards.values()] });
  }
  if (action === 'card' || action === 'save') {
    const id = cardId(action === 'card' ? url.searchParams.get('card') : body.cardId ?? body.card);
    const resolution = { cardId: id, requestedCardId: id, isVariant: false };
    const rows = current.rows.filter(row => row.card_id === id);
    const rev = await revision(rows);
    if (action === 'card') return json({ success: true, root, revision: rev, abilities: rows, ...resolution });
    if (typeof body.baseRevision !== 'string' || !body.baseRevision) throw new ApiError(400, 'baseRevision is required');
    if (body.baseRevision !== rev) return json({ success: false, error: 'Card abilities changed since they were loaded', conflict: { revision: rev, abilities: rows }, ...resolution }, 409);
    const saved = normalize(body.abilities, body.cardImplemented);
    const statements = [db.prepare('DELETE FROM card_abilities WHERE root_name = ? AND card_id = ?').bind(root, id)];
    statements.push(db.prepare(`INSERT INTO card_abilities(root_name, ${columns.join(',')}) SELECT ?, ?, ${columns.slice(1).map(key => `json_extract(value, '$.${key}')`).join(',')} FROM json_each(?)`).bind(root, id, JSON.stringify(saved)));
    statements.push(db.prepare('SELECT * FROM card_abilities WHERE root_name = ? AND card_id = ? ORDER BY created_at, id').bind(root, id));
    const result = await mutate(db, root, current, token.token_name, statements);
    // Readback is in the same mutation batch so a later editor cannot alter the save response.
    const abilities = result[result.length - 2].results;
    return json({ success: true, conflict: false, revision: await revision(abilities), abilities, ...resolution });
  }
  if (action === 'ensure-cards') {
    if (!Array.isArray(body.cardIds) || body.cardIds.length > 10000) throw new ApiError(400, 'Invalid card IDs');
    const existing = new Set(current.rows.map(row => row.card_id));
    const ids = [...new Set(body.cardIds.map(cardId))].filter(id => !existing.has(id));
    // json_each keeps large imports below D1's bound-parameter and query-count limits.
    if (ids.length) await mutate(db, root, current, token.token_name, [db.prepare('INSERT INTO card_abilities(root_name, card_id) SELECT ?, value FROM json_each(?)').bind(root, JSON.stringify(ids))]);
    return json({ success: true, insertedCount: ids.length });
  }
  if (action === 'restore') {
    const entry = await db.prepare('SELECT * FROM checkpoints WHERE id = ? AND root_name = ?').bind(Number(body.checkpointId) || 0, root).first();
    if (!entry) throw new ApiError(404, 'Checkpoint not found');
    const fields = ['id', 'root_name', ...columns, 'created_at', 'updated_at'];
    await mutate(db, root, current, token.token_name, [db.prepare('DELETE FROM card_abilities WHERE root_name = ?').bind(root), db.prepare(`INSERT INTO card_abilities(${fields.join(',')}) SELECT ${fields.map(key => `json_extract(payload, '$.${key}')`).join(',')} FROM checkpoint_abilities WHERE checkpoint_id = ?`).bind(entry.id)]);
    return json({ success: true, restored: true, abilityCount: entry.ability_count });
  }
  throw new ApiError(404, 'Unknown Card Code service action');
}
export default {
  async fetch(request, env) {
    try { return await handle(request, env); }
    catch (error) { return json({ success: false, error: error instanceof ApiError ? error.message : 'Card Code service failed' }, error.status || 500); }
  },
  async scheduled(event, env, ctx) { ctx.waitUntil(checkpoint(env.DB, env.CARD_CODE_ROOT, 'daily-schedule')); },
};
