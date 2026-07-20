// Run every regression suite in this directory and summarise. Exits non-zero if any fail.
//   node regression/run-all.mjs
//   PREMIER=100431 TWINSUNS=201009 node regression/run-all.mjs
import { readdirSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
const suites = readdirSync(here).filter(f => f.endsWith('.mjs') && f !== 'run-all.mjs').sort();

const results = [];
for (const s of suites) {
  process.stdout.write(`\n──────── ${s} ────────\n`);
  const r = spawnSync(process.execPath, [join(here, s)], { stdio: 'inherit', env: process.env });
  results.push({ suite: s, ok: r.status === 0 });
}

console.log('\n════════ SUMMARY ════════');
for (const r of results) console.log(`  ${r.ok ? 'PASS' : 'FAIL'}  ${r.suite}`);
const failed = results.filter(r => !r.ok);
console.log(failed.length ? `\n${failed.length} suite(s) FAILED` : '\nAll suites passed');
process.exit(failed.length ? 1 : 0);
