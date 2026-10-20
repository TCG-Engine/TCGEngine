// Convert the local export into a seed for an EMPTY Cloudflare workspace.
// The guard makes accidental import over authored data fail without deleting anything.
import { readFileSync, writeFileSync } from 'node:fs';
import { revision } from './worker.mjs';
const [source, target] = process.argv.slice(2);
if (!source || !target) throw new Error('Usage: node prepare-import.mjs export.json import.sql');
const data = JSON.parse(readFileSync(source, 'utf8'));
if (data.root !== 'GrandArchiveSim' || !Array.isArray(data.abilities) || await revision(data.abilities) !== data.revision) throw new Error('Invalid export or revision mismatch');
const fields = ['id', 'root_name', 'card_id', 'macro_name', 'ability_type', 'ability_code', 'prereq_code', 'listener_zones', 'ability_name', 'is_implemented', 'created_at', 'updated_at'];
const value = x => x == null ? 'NULL' : typeof x === 'number' ? String(x) : `CAST(X'${Buffer.from(String(x)).toString('hex')}' AS TEXT)`;
const statements = ["INSERT INTO write_guard(ok) VALUES ((SELECT count(*) = 0 FROM card_abilities WHERE root_name = 'GrandArchiveSim'));", 'DELETE FROM write_guard;'];
for (const row of data.abilities) {
  if (row.root_name !== data.root) throw new Error('Export contains another workspace');
  statements.push(`INSERT INTO card_abilities(${fields.join(',')}) VALUES (${fields.map(key => value(row[key])).join(',')});`);
}
statements.push("UPDATE workspace SET version = version + 1 WHERE root = 'GrandArchiveSim';");
writeFileSync(target, statements.join('\n') + '\n', { mode: 0o600 });
console.log(`Prepared ${data.abilities.length} abilities; revision ${data.revision}`);
