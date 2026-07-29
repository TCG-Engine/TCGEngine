import { chromium } from 'playwright';

const url = process.argv[2];
const browser = await chromium.launch();
const page = await browser.newPage();
const errs = [];
page.on('pageerror', e => errs.push(e.message));
await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
await page.waitForFunction(() => window.archetypeMatchups, null, { timeout: 20000 });

const out = await page.evaluate(() => {
  const list = window.archetypeMatchups;
  return {
    count: list.length,
    deckSum: list.reduce((s, a) => s + a.deckCount, 0),
    matchSum: list.reduce((s, a) => s + a.totalMatches, 0),
    sortedByDecks: list.every((a, i) => i === 0 || list[i - 1].deckCount >= a.deckCount),
    oppSorted: list.every(a => a.opponents.every((o, i) => i === 0 || a.opponents[i - 1].matches >= o.matches)),
    oppReconciles: list.every(a => a.opponents.reduce((s, o) => s + o.matches, 0) === a.totalMatches),
    top: list.slice(0, 4).map(a => ({
      leader: a.leaderName, base: a.baseLabel, decks: a.deckCount,
      matches: a.totalMatches, opps: a.opponents.length,
      ge10: a.opponents.filter(o => o.matches >= 10).length,
    })),
  };
});
console.log(JSON.stringify({ jsErrors: errs, ...out }, null, 2));
await browser.close();
