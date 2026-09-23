// Cross-browser check for the main menu's "Games in Progress" panel (owner, 2026-09-21: one chip per public game —
// leader vs leader + Spectate; Twin Suns seats as a leader/leader/base stack like SWUDeck).
// Visual case: SWUSim/Tests/Visual/Menu_GamesInProgress.md
//
// PART A — layout, from a FIXTURE: SWUSim/PublicGames.php is intercepted (page.route) and answered with a fixed list —
//   two 1v1 games (Premier, Eternal), a 3-seat Twin Suns game and a 4-seat Team Suns game — so the chips can be checked
//   without real public games running. Asserts: the count, the filter options, one chip per game, one identity stack
//   per seat, TWO leaders per Twin/Team Suns seat, Team Suns grouped 2 vs 2, every art image actually loads, the chips
//   fit the panel (no horizontal overflow) at desktop and phone width, the filter narrows the list and shows the
//   filtered empty state, and Spectate navigates to the game's spectate URL.
// PART B — live, end to end: two GUESTS queue a public Premier match over HTTP; the real endpoint must list it with
//   both leaders, and a THIRD guest (no account) must be able to open its spectate link (guests may spectate public
//   games since 2026-09-21). ⚠ Local dev lets every viewer spectate regardless, so B proves the listing + link, and
//   SWUSim/DevTools/tests/public_games_test.php proves the auth rule with dev mode OFF.
//
// Usage: node swusim-games-in-progress-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit   SKIP_LIVE=1
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const OUT = process.env.OUT || '/tmp';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
const ART = (id) => `/TCGEngine/AppCore/SWU/Images/WebpImages/${id}.webp`;
const card = (id, name) => ({ id, name, url: ART(id) });
const seat = (n, leaders, base, team = 0) => ({ seat: n, team, leaders: leaders.map(l => card(l, l)), base: card(base, base) });
const FIXTURE = {
  success: true, count: 4,
  formats: [{ id: 'eternal', name: 'Eternal' }, { id: 'premier', name: 'Premier' }, { id: 'teamsuns', name: 'Team Suns' }, { id: 'twinsuns', name: 'Twin Suns' }],
  games: [
    { gameName: '900001', format: 'premier', formatName: 'Premier', isTeam: false, spectateUrl: '/TCGEngine/NextTurn.php?playerID=S&gameName=900001&folderPath=SWUSim', lastUpdatedAt: 4,
      seats: [seat(1, ['SOR_005'], 'SOR_019'), seat(2, ['LAW_004'], 'JTL_026')] },
    { gameName: '900002', format: 'eternal', formatName: 'Eternal', isTeam: false, spectateUrl: '/TCGEngine/NextTurn.php?playerID=S&gameName=900002&folderPath=SWUSim', lastUpdatedAt: 3,
      seats: [seat(1, ['SOR_010'], 'SOR_022'), seat(2, ['JTL_005'], 'SOR_024')] },
    { gameName: '900003', format: 'twinsuns', formatName: 'Twin Suns', isTeam: false, spectateUrl: '/TCGEngine/NextTurn.php?playerID=S&gameName=900003&folderPath=SWUSim', lastUpdatedAt: 2,
      seats: [seat(1, ['SEC_002', 'SHD_002'], 'SHD_026'), seat(2, ['TWI_004', 'SOR_014'], 'TWI_027'), seat(3, ['LOF_012', 'SOR_005'], 'LOF_019')] },
    { gameName: '900004', format: 'teamsuns', formatName: 'Team Suns', isTeam: true, spectateUrl: '/TCGEngine/NextTurn.php?playerID=S&gameName=900004&folderPath=SWUSim', lastUpdatedAt: 1,
      seats: [seat(1, ['SEC_002', 'SHD_002'], 'SHD_026', 1), seat(2, ['TWI_004', 'SOR_014'], 'TWI_027', 2),
              seat(3, ['LOF_012', 'SOR_005'], 'LOF_019', 1), seat(4, ['LAW_004', 'JTL_005'], 'JTL_026', 2)] },
  ],
};

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

// ── PART A ───────────────────────────────────────────────────────────────────────────────────────────
for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    for (const width of [1672, 1280, 390]) {
      const tag = `${width}px`;
      const ctx = await browser.newContext({ viewport: { width, height: width > 500 ? 941 : 844 } });
      const page = await ctx.newPage();
      await page.route('**/SWUSim/PublicGames.php*', r => r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(FIXTURE) }));
      await page.route('**/NextTurn.php?playerID=S&gameName=9000*', r => r.fulfill({ status: 200, contentType: 'text/html', body: '<p>spectate stub</p>' }));
      await page.goto(MENU, { waitUntil: 'load' });
      await page.waitForFunction(() => document.querySelectorAll('.swu-game-chip').length > 0, null, { timeout: 8000 }).catch(() => {});
      await page.waitForFunction(() => [...document.querySelectorAll('.swu-idstack img')].every(i => i.complete), null, { timeout: 8000 }).catch(() => {});

      const s = await page.evaluate(() => {
        const chips = [...document.querySelectorAll('.swu-game-chip')];
        const list = document.querySelector('#active-games-list').getBoundingClientRect();
        return {
          count: document.getElementById('active-game-count').textContent.trim(),
          options: [...document.querySelectorAll('#swu-games-filter option')].map(o => o.value).join(','),
          chips: chips.length,
          stacks: chips.map(c => c.querySelectorAll('.swu-idstack').length).join(','),
          leaders: chips.map(c => [...c.querySelectorAll('.swu-idstack')].map(st => st.querySelectorAll('.swu-idstack__leader').length).join('')).join(','),
          bases: chips.map(c => c.querySelectorAll('.swu-idstack__base').length).join(','),
          teams: chips.map(c => c.querySelectorAll('.swu-game-team').length).join(','),
          vs: chips.map(c => c.querySelectorAll('.swu-game-vs').length).join(','),
          broken: [...document.querySelectorAll('.swu-idstack img')].filter(i => !i.complete || i.naturalWidth === 0).length,
          overflowChips: chips.filter(c => c.scrollWidth > c.clientWidth + 1 || c.getBoundingClientRect().right > list.right + 1).length,
          // A 1v1 chip must keep leader · vs · leader on ONE line (the reference layout), at every width.
          oneLine1v1: chips.slice(0, 2).every(c => { const t = [...c.querySelectorAll('.swu-idstack')].map(e => Math.round(e.getBoundingClientRect().top)); return t.length === 2 && Math.abs(t[0] - t[1]) < 4; }),
          label: chips[2] ? chips[2].querySelector('.swu-game-chip__format').textContent : '',
          empty: getComputedStyle(document.getElementById('swu-active-empty')).display,
          pageOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
        };
      });
      ok(engine, `${tag}: the count shows every public game`, s.count === '4', s.count);
      ok(engine, `${tag}: the filter offers All + each format present`, s.options === ',eternal,premier,teamsuns,twinsuns', s.options);
      ok(engine, `${tag}: one chip per game`, s.chips === 4, String(s.chips));
      ok(engine, `${tag}: one identity stack per seat (2, 2, 3, 4)`, s.stacks === '2,2,3,4', s.stacks);
      ok(engine, `${tag}: one leader per 1v1 seat, two per Twin/Team Suns seat`, s.leaders === '11,11,222,2222', s.leaders);
      ok(engine, `${tag}: every seat shows its base`, s.bases === '2,2,3,4', s.bases);
      ok(engine, `${tag}: Team Suns is grouped team vs team (one "vs"); free-for-all has none`, s.teams === '0,0,0,2' && s.vs === '1,1,0,1', `teams=${s.teams} vs=${s.vs}`);
      ok(engine, `${tag}: every card image loads`, s.broken === 0, String(s.broken));
      ok(engine, `${tag}: a 1v1 chip keeps leader vs leader on one line`, s.oneLine1v1);
      ok(engine, `${tag}: a free-for-all chip names its player count`, s.label === 'Twin Suns · 3 players', s.label);
      ok(engine, `${tag}: chips fit the panel`, s.overflowChips === 0, String(s.overflowChips));
      ok(engine, `${tag}: no empty state while games are listed`, s.empty === 'none', s.empty);
      ok(engine, `${tag}: no horizontal page scroll`, s.pageOverflow <= 0, String(s.pageOverflow));
      await page.$eval('.swu-active-card', el => el.scrollIntoView());
      await page.screenshot({ path: `${OUT}/games-in-progress-${engine}-${width}.png` });

      // Filter: Twin Suns only → one chip; the filtered empty state for a format with no games is covered by a stale
      // selection being reset, so here just check narrowing and the reset to All.
      await page.selectOption('#swu-games-filter', 'twinsuns');
      const f = await page.evaluate(() => [...document.querySelectorAll('.swu-game-chip')].map(c => c.getAttribute('data-format')).join(','));
      ok(engine, `${tag}: the filter narrows to one format`, f === 'twinsuns', f);
      await page.evaluate(() => { _swuPublicGames = _swuPublicGames.filter(g => g.format !== 'twinsuns'); swuRenderPublicGames(); });
      const e = await page.evaluate(() => [getComputedStyle(document.getElementById('swu-active-empty')).display, document.getElementById('swu-active-empty-title').textContent]);
      ok(engine, `${tag}: a format with no games shows the filtered empty state`, e[0] !== 'none' && e[1] === 'No games in this format', e.join(' | '));
      await page.selectOption('#swu-games-filter', '');
      await page.evaluate(() => refreshOpenGames());
      await page.waitForTimeout(300);

      if (width === 1672) {
        await Promise.all([page.waitForURL(/NextTurn\.php\?playerID=S&gameName=900001/, { timeout: 8000 }).catch(() => {}),
                           page.click('.swu-game-chip >> nth=0 >> .swu-spectate-btn')]);
        ok(engine, `${tag}: Spectate opens the game's spectate URL`, /NextTurn\.php\?playerID=S&gameName=900001&folderPath=SWUSim/.test(page.url()), page.url());
      }
      await ctx.close();
    }
  } catch (err) {
    ok(engine, 'harness ran', false, String(err && err.message ? err.message : err));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}

// ── PART B (live) ────────────────────────────────────────────────────────────────────────────────────
if (!process.env.SKIP_LIVE) {
  const deckText = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/meta-2026-09/vader_yellow.txt', import.meta.url), 'utf8')
    .split('\n').filter(l => !l.startsWith('#')).join('\n').trim();
  const join = async () => {
    const r = await fetch(BASE + 'APIs/Lobbies/JoinQueue.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ rootName: 'SWUSim', format: 'premier', queueType: 'bo1', deckLink: deckText }) });
    return r.json();
  };
  const poll = async (j) => {
    for (let i = 0; i < 20; i++) {
      const r = await fetch(BASE + 'APIs/Lobbies/PollLobbyUpdates.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ lobbyID: j.lobbyID || '', playerID: String(j.playerID || 1), authKey: j.authKey || '', rootName: 'SWUSim' }) });
      const d = await r.json().catch(() => ({}));
      if (d.gameName) return d.gameName;
      await new Promise(res => setTimeout(res, 500));
    }
    return j.gameName || '';
  };
  const a = await join(); const b = await join();
  const gameName = b.gameName || await poll(a);
  ok('live', 'two guests paired into a public Premier game', !!gameName, JSON.stringify({ a: a.message, b: b.message }));
  if (gameName) {
    // The game registers in the active index when a seat first loads it.
    const browser = await chromium.launch();
    const seatPage = await (await browser.newContext()).newPage();
    await seatPage.goto(`${BASE}NextTurn.php?gameName=${gameName}&playerID=${b.playerID || 2}&folderPath=SWUSim&authKey=${b.authKey || ''}`, { waitUntil: 'load' }).catch(() => {});
    await seatPage.waitForTimeout(1500);
    // The endpoint shares its list through a 10s APCu cache, so retry past one cache window before calling it missing.
    let list = {}, g = null;
    for (let i = 0; i < 16 && !g; i++) {
      if (i) await new Promise(res => setTimeout(res, 1000));
      list = await (await fetch(BASE + 'SWUSim/PublicGames.php')).json();
      g = (list.games || []).find(x => x.gameName === String(gameName));
    }
    ok('live', 'the real endpoint lists the new public game', !!g, JSON.stringify((list.games || []).map(x => x.gameName)));
    ok('live', 'it carries both seats with a leader and a base each',
       !!g && g.seats.length === 2 && g.seats.every(s => s.leaders.length === 1 && s.base && /WebpImages\//.test(s.leaders[0].url)), JSON.stringify(g && g.seats));
    const guest = await (await browser.newContext()).newPage();   // a fresh context: no session, no seat key
    const resp = await guest.goto(BASE.replace(/\/TCGEngine\/$/, '') + g.spectateUrl, { waitUntil: 'load' }).catch(e => null);
    await guest.waitForTimeout(1000);
    const onLogin = /LoginPage\.php/.test(guest.url());
    ok('live', 'a guest can open the spectate link (not bounced to login)', resp && resp.status() === 200 && !onLogin, guest.url());
    await browser.close();
  }
}

for (const [engine, name, pass, extra] of results) {
  console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
}
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
