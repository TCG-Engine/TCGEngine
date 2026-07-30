// Parity: for a SINGLE-tournament selection the aggregate API must reproduce exactly what the
// single-tournament page computes client-side. If these two ever disagree, the aggregate view is
// quietly showing different numbers than the per-event view for the same data.
import { chromium } from 'playwright';

const id = process.argv[2];
const base = 'http://localhost:3100/TCGEngine';
const browser = await chromium.launch();
const page = await browser.newPage();
await page.goto(`${base}/Stats/MeleeTournamentResults.php?id=${id}`, { waitUntil: 'networkidle' });
await page.waitForFunction(() => window.decksData && window.archetypeMatchups, null, { timeout: 30000 });

const client = await page.evaluate(() => ({
  leaderMetaShare:   calculateLeaderMetaShare(window.decksData),
  comboMetaShare:    calculateLeaderComboMetaShare(window.decksData),
  leaderPerformance: calculateLeaderPerformance(window.decksData),
  archetypes:        window.archetypeMatchups,
}));

const server = await (await fetch(`${base}/APIs/GetMeleeAggregate.php?ids=${id}`)).json();
if (!server.success) {
  console.log('FAIL — API error:', server.message);
  process.exit(1);
}

const fails = [];
const norm = v => JSON.parse(JSON.stringify(v));

for (const k of ['leaderMetaShare', 'comboMetaShare', 'leaderPerformance']) {
  const a = norm(client[k]), b = norm(server[k] || []);
  if (a.length !== b.length) { fails.push(`${k} length ${a.length} vs ${b.length}`); continue; }
  for (let i = 0; i < a.length; i++) {
    if (JSON.stringify(a[i]) !== JSON.stringify(b[i])) {
      fails.push(`${k}[${i}] differs:\n      client ${JSON.stringify(a[i])}\n      server ${JSON.stringify(b[i])}`);
      break;
    }
  }
}

const ca = new Map(client.archetypes.map(a => [a.key, a]));
const sa = new Map((server.archetypes || []).map(a => [a.key, a]));
if (ca.size !== sa.size) fails.push(`archetype count ${ca.size} vs ${sa.size}`);
for (const [key, c] of ca) {
  const s = sa.get(key);
  if (!s) { fails.push(`missing archetype ${key}`); continue; }
  if (c.deckCount !== s.deckCount || c.totalMatches !== s.totalMatches) {
    fails.push(`${key}: decks ${c.deckCount}/${s.deckCount} matches ${c.totalMatches}/${s.totalMatches}`);
    continue;
  }
  const shape = o => [o.key, o.matches, o.matchWins, o.matchLosses, o.matchDraws, !!o.isMirror];
  const co = JSON.stringify(c.opponents.map(shape));
  const so = JSON.stringify((s.opponents || []).map(shape));
  if (co !== so) fails.push(`${key}: opponents differ\n      client ${co.slice(0, 200)}\n      server ${so.slice(0, 200)}`);
}

console.log(`client archetypes ${ca.size}, server ${sa.size}`);
if (fails.length) {
  console.log('FAIL');
  fails.slice(0, 8).forEach(f => console.log('  -', f));
  await browser.close();
  process.exit(1);
}
console.log('PASS — aggregate API matches single-tournament computation');
await browser.close();
