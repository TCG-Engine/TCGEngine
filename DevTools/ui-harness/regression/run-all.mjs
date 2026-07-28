// Run every regression suite (grouped by rootname subfolder) and summarise.
//   node regression/run-all.mjs
//   node regression/run-all.mjs SWUDeck          # only one rootname
//   PREMIER=100431 TWINSUNS=201009 node regression/run-all.mjs
//
// Per-suite exit code: 0 = all checks passed · 1 = a check FAILED (real regression) ·
// 2 = ENVIRONMENT NOT READY (stack/deck/login setup problem, not a regression). run-all keeps those
// three outcomes distinct so a red result is trustworthy — it exits 1 only on a genuine regression.
import { readdirSync, statSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
const only = process.argv[2] || null;   // optional rootname filter

// Suites live in per-rootname subfolders (SWUDeck/, SWUSim/, …). Top-level .mjs (run-all, lib) aren't suites.
const roots = readdirSync(here)
  .filter(d => statSync(join(here, d)).isDirectory())
  .filter(d => !only || d === only)
  .sort();

const suites = [];
for (const root of roots) {
  for (const f of readdirSync(join(here, root)).filter(f => f.endsWith('.mjs')).sort()) {
    suites.push({ root, file: f, path: join(here, root, f) });
  }
}

if (suites.length === 0) {
  console.error(only ? `No suites under rootname "${only}".` : 'No suites found.');
  process.exit(2);
}

const results = [];
for (const s of suites) {
  process.stdout.write(`\n──────── ${s.root}/${s.file} ────────\n`);
  const r = spawnSync(process.execPath, [s.path], { stdio: 'inherit', env: process.env });
  results.push({ ...s, code: r.status });
}

console.log('\n════════ SUMMARY ════════');
const label = c => (c === 0 ? 'PASS' : c === 2 ? 'ENV ' : 'FAIL');
for (const r of results) console.log(`  ${label(r.code)}  ${r.root}/${r.file}`);

const failed = results.filter(r => r.code === 1);
const env = results.filter(r => r.code === 2);
if (env.length) console.log(`\n${env.length} suite(s) could not run — environment not ready (see above); not counted as a regression.`);
console.log(failed.length ? `${failed.length} suite(s) FAILED` : (env.length ? '(no regressions)' : '\nAll suites passed'));

// Exit 1 ONLY on a genuine regression; 2 if the run was purely blocked by the environment; else 0.
process.exit(failed.length ? 1 : (env.length ? 2 : 0));
