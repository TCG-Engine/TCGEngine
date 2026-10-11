// Twin Suns seat usernames on the home previews and in chat, in Chromium, Firefox and WebKit.
// Visual spec: SWUSim/Tests/Visual/TwinSuns_SeatUsernames.md
// Usage: node swusim-seat-usernames-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SCHEMA_TS = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/TwinSuns_SeatUsernames.md', import.meta.url), 'utf8');
const SCHEMA_PM = `## GIVEN\nCommonSetup: bbw/rrk/{myResources:5; theirResources:5}\nWithGamePhase: ActionPhase\nWithActivePlayer: 1\n\n## WHEN\n\n## EXPECT\nTURNPLAYER:1\n`;
const SHOTS = process.env.SHOTS_DIR || os.tmpdir();
// Seat 2 a normal name, seat 3 a long one (truncation), seat 4 an HTML-injection attempt (escaping).
const NAMES = { '2': 'claudebot2', '3': 'averyveryverylongusername_thatkeepsgoing', '4': '<img src=x onerror=window.__pwned=1>' };

const NAMES_3 = NAMES['3'], NAMES_4 = NAMES['4'];
// What the server sends for a match game in which seat 1 is a guest: every seat's display name.
const SERVED = { usernames: NAMES, display: { '1': 'Guest P1', '2': 'claudebot2', '3': NAMES_3, '4': NAMES_4 } };
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 900s'); report(); process.exit(9); }, 900000).unref();

async function makeGame(schema) {
  const res = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema }) });
  const j = await res.json();
  if (!j.gameName) throw new Error('TestSchemaSetup failed: ' + JSON.stringify(j));
  return String(j.gameName);
}
const url = (gn, pid, mobile = false) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${gn}&playerID=${pid}&authKey=testschema`
  + (mobile ? '&swuLayout=mobile' : '');

// names: serve the page with SWU_SEAT_USERNAMES already filled — the shape a real match game has at load, so
// everything built at load (the view labels, the log) sees them too. A schema game serves "{}".
async function open(browser, gn, pid, viewport, mobile = false, names = null) {
  const ctx = await browser.newContext({ viewport });
  const page = await ctx.newPage();
  if (names) {
    await page.route('**/NextTurn.php*', async (route) => {
      const r = await route.fetch();
      const u = names.usernames || names, dsp = names.display || null;
      let body = (await r.text()).replace('window.SWU_SEAT_USERNAMES = {};', 'window.SWU_SEAT_USERNAMES = ' + JSON.stringify(u) + ';');
      if (dsp) body = body.replace(/window\.SWU_SEAT_DISPLAY_NAMES = \{[^;]*\};/, 'window.SWU_SEAT_DISPLAY_NAMES = ' + JSON.stringify(dsp) + ';');
      await route.fulfill({ response: r, body });
    });
  }
  await page.goto(url(gn, pid, mobile), { waitUntil: 'domcontentloaded' });
  const sel = mobile ? '#swuHomeStrips .swu-seat-row' : '#swuHomeStrips .swu-home-strip';
  if (names && names.oneVsOne) await page.waitForFunction(() => typeof window.swuRenderGameLog === 'function' && window.LiveSeatsData !== undefined, null, { timeout: 90000 });
  else await page.waitForSelector(sel, { timeout: 90000 });   // generous: a busy container (a bot sweep) slows the first poll
  return { ctx, page, sel };
}

// Read each tile's seat label: its text, whether it is named, and whether the name overflows the tile.
const readLabels = (page, sel, labelCls) => page.evaluate(([sel, labelCls]) => {
  return [...document.querySelectorAll(sel)].map((tile) => {
    const lab = tile.querySelector('.' + labelCls);
    const name = lab && lab.querySelector('.swu-seat-name');
    const tr = tile.getBoundingClientRect();
    const zoom = tile.querySelector('.swu-mb-zoom, .swu-sr-zoom');
    const zr = zoom ? zoom.getBoundingClientRect() : null;
    return { seat: tile.getAttribute('data-seat'), text: lab ? lab.textContent : null, named: !!name,
             title: lab ? lab.getAttribute('title') : null,
             truncated: name ? name.scrollWidth > name.clientWidth + 1 : false,
             zoomInside: zr ? (zr.right <= tr.right + 1 && zr.left >= tr.left - 1) : null,
             injected: !!tile.querySelector('img[src="x"]') };
  });
}, [sel, labelCls]);


for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const gn = await makeGame(SCHEMA_TS);

    // ── Desktop, seat 1 ──
    const d = await open(browser, gn, '1', { width: 1700, height: 1050 });
    ok(engine, 'desktop: CHAT_SEAT_SUFFIX is on at 4 seats', (await d.page.evaluate(() => window.CHAT_SEAT_SUFFIX)) === true);
    const plain = await readLabels(d.page, d.sel, 'swu-mb-seat');
    ok(engine, 'desktop: no match record -> guest tiles keep bare "P<n>"',
      JSON.stringify(plain.map((l) => l.text)) === JSON.stringify(['P2', 'P3', 'P4']) && plain.every((l) => !l.named), JSON.stringify(plain.map((l) => l.text)));
    await d.ctx.close();
    const dn = await open(browser, gn, '1', { width: 1700, height: 1050 }, false, SERVED);
    ok(engine, 'desktop: served names reached the page', (await dn.page.evaluate(() => window.SWU_SEAT_USERNAMES['2'])) === 'claudebot2');
    const named = await readLabels(dn.page, d.sel, 'swu-mb-seat');
    const by = Object.fromEntries(named.map((l) => [l.seat, l]));
    ok(engine, 'desktop: seat 2 reads "claudebot2P2"', by['2'] && by['2'].named && by['2'].text === 'claudebot2P2', JSON.stringify(by['2']));
    ok(engine, 'desktop: tooltip is "claudebot2 (P2)"', by['2'] && by['2'].title === 'claudebot2 (P2)', by['2'] && by['2'].title);
    ok(engine, 'desktop: long name is truncated', by['3'] && by['3'].truncated, JSON.stringify(by['3']));
    ok(engine, 'desktop: HTML in a name is escaped, not rendered', by['4'] && !by['4'].injected && by['4'].text === NAMES['4'] + 'P4'
      && !(await dn.page.evaluate(() => window.__pwned === 1)), JSON.stringify(by['4']));
    ok(engine, 'desktop: every Zoom button stays inside its tile', named.every((l) => l.zoomInside === true), JSON.stringify(named.map((l) => l.zoomInside)));
    const chat = await dn.page.evaluate(() => [_ChatMessageLabel({ playerID: 2, message: 'hi' }),
      _ChatMessageLabel({ playerID: 3, message: 'x', to: [1, 4] }), _ChatWhisperStubText({ playerID: 2, to: [3] }),
      _ChatMessageLabel({ playerID: 1, message: 'guest' })]);
    ok(engine, 'chat: "claudebot2 (P2):"', chat[0] === 'claudebot2 (P2):', chat[0]);
    ok(engine, 'chat: whisper label names seats', chat[1] === NAMES['3'] + ' (P3) → you, ' + NAMES['4'] + ' (P4):', chat[1]);
    ok(engine, 'chat: whisper stub names seats', chat[2] === 'claudebot2 (P2) whispered something to ' + NAMES['3'] + ' (P3)', chat[2]);
    ok(engine, 'chat: a seat with no username stays "P1:"', chat[3] === 'P1:', chat[3]);
    // Matchup labels and Zoom tooltips.
    const views = await dn.page.evaluate(() => (window.swuViews || []).map((v) => v.label));
    ok(engine, 'views: matchup labels are named', JSON.stringify(views) === JSON.stringify(['Home', 'vs claudebot2 (P2)', 'vs ' + NAMES_3 + ' (P3)', 'vs ' + NAMES_4 + ' (P4)']), JSON.stringify(views));
    const zoomTitle = await dn.page.getAttribute('#swuHomeStrips .swu-home-strip[data-seat="2"] .swu-mb-zoom', 'title');
    ok(engine, 'zoom tooltip is named', zoomTitle === 'Open your board vs claudebot2 (P2)', zoomTitle);
    // The game log: append entries the way a poll does and render them.
    const log = await dn.page.evaluate(() => {
      window.GameLogData = (window.GameLogData && window.GameLogData !== '-' ? window.GameLogData + '<NL>' : '')
        + 'COUNTER|0|P2 took the blast counter (1 damage to each enemy base)<NL>'
        + 'PLAY|0|P3 played [[SOR_095|P2 Card]] on P4\'s unit<NL>PASS|0|P1 passed';
      window.swuRenderGameLog();
      const rows = [...document.querySelectorAll('#swuLogPanel .swu-log-entry')].slice(-3);
      return { text: rows.map((r) => r.textContent), links: rows.map((r) => r.querySelectorAll('.swu-card-link').length),
               injected: !!document.querySelector('#swuLogPanel img[src="x"]') };
    });
    ok(engine, 'log: a named seat reads "claudebot2 (P2)"', log.text[0] === 'claudebot2 (P2) took the blast counter (1 damage to each enemy base)', log.text[0]);
    ok(engine, 'log: card link text is left alone, other seats named', log.text[1] === NAMES_3 + ' (P3) played P2 Card on ' + NAMES_4 + " (P4)'s unit" && log.links[1] === 1, JSON.stringify([log.text[1], log.links[1]]));
    ok(engine, 'log: a guest seat reads "Guest P1"', log.text[2] === 'Guest P1 passed', log.text[2]);
    ok(engine, 'log: an HTML name is escaped', !log.injected && !(await dn.page.evaluate(() => window.__pwned === 1)));
    await dn.page.screenshot({ path: path.join(SHOTS, `seat-names-${engine}-desktop.png`) }).catch(() => {});
    await dn.ctx.close();

    // ── Mobile rows, seat 1 ──
    const m = await open(browser, gn, '1', { width: 400, height: 860 }, true, SERVED);
    const mrows = await readLabels(m.page, m.sel, 'swu-sr-seat');
    const mby = Object.fromEntries(mrows.map((l) => [l.seat, l]));
    ok(engine, 'mobile: seat 2 row reads "claudebot2P2"', mby['2'] && mby['2'].text === 'claudebot2P2', JSON.stringify(mby['2']));
    ok(engine, 'mobile: long name is truncated', mby['3'] && mby['3'].truncated, JSON.stringify(mby['3']));
    ok(engine, 'mobile: every Zoom button stays inside its row', mrows.every((l) => l.zoomInside === true), JSON.stringify(mrows.map((l) => l.zoomInside)));
    ok(engine, 'mobile: no horizontal page scroll', !(await m.page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1)));
    await m.page.screenshot({ path: path.join(SHOTS, `seat-names-${engine}-mobile.png`) }).catch(() => {});
    await m.ctx.close();

    // ── Premier (1v1): the suffix stays off ──
    const pm = await makeGame(SCHEMA_PM);
    const pctx = await browser.newContext({ viewport: { width: 1500, height: 950 } });
    const pp = await pctx.newPage();
    await pp.goto(url(pm, '1'), { waitUntil: 'domcontentloaded' });
    await pp.waitForFunction(() => typeof window.CHAT_SEAT_SUFFIX !== 'undefined', null, { timeout: 90000 });
    ok(engine, 'premier: CHAT_SEAT_SUFFIX is off', (await pp.evaluate(() => window.CHAT_SEAT_SUFFIX)) === false);
    const pl = await pp.evaluate(() => { window.SWU_SEAT_USERNAMES = { '2': 'claudebot2' }; return _ChatMessageLabel({ playerID: 2, message: 'hi' }); });
    ok(engine, 'premier: chat stays "claudebot2:"', pl === 'claudebot2:', pl);
    await pctx.close();

    // ── 1v1 game log: usernames, "Guest PN" for a guest, "Arenabot" for the bot; P<n> with no match record ──
    const logOf = (page, lines) => page.evaluate((lines) => {
      window.GameLogData = (window.GameLogData && window.GameLogData !== '-' ? window.GameLogData + '<NL>' : '') + lines.join('<NL>');
      window.swuRenderGameLog();
      return [...document.querySelectorAll('#swuLogPanel .swu-log-entry')].slice(-lines.length).map((r) => r.textContent);
    }, lines);
    const LINES = ['INITIATIVE|0|P1 took the initiative', "PLAY|0|P2 played [[SOR_095|P1 Card]] on P1's unit"];
    const v1 = await open(browser, pm, '1', { width: 1500, height: 950 }, false,
      { oneVsOne: true, usernames: { '1': 'claudebot1' }, display: { '1': 'claudebot1', '2': 'Guest P2' } });
    const l1 = await logOf(v1.page, LINES);
    ok(engine, '1v1 log: a username, no seat suffix', l1[0] === 'claudebot1 took the initiative', l1[0]);
    ok(engine, '1v1 log: a guest is "Guest P2"; card link untouched', l1[1] === "Guest P2 played P1 Card on claudebot1's unit", l1[1]);
    await v1.ctx.close();
    const v2 = await open(browser, pm, '1', { width: 1500, height: 950 }, false, { oneVsOne: true, usernames: {}, display: { '2': 'Arenabot' } });
    const l2 = await logOf(v2.page, LINES);
    ok(engine, '1v1 log: the bot is "Arenabot"; a seat with no name stays "P1"', l2[1] === "Arenabot played P1 Card on P1's unit", l2[1]);
    await v2.ctx.close();
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.stack || e));
  } finally {
    if (browser) await browser.close();
  }
}
report();
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
