// The chat panel on the Waiting Room and the Sideboard, in Chromium, Firefox and WebKit.
//   Waiting Room: pinned left at 18% (clamped 180-320px), seat-tinted rails, composer at the bottom,
//   roster still 2x2, autoscroll only when already at the bottom, guest gets no composer, and below
//   900px it collapses to a 💬 drawer. Sideboard: the same panel plus the previous game's log,
//   rendered with plain card names.
// Spec: docs/superpowers/specs/2026-09-22-chat-panel-waiting-room-sideboard-design.md
// Visual: SWUSim/Tests/Visual/ChatPanel_WaitingRoom.md · ChatPanel_Sideboard.md
// Usage: node chatpanel-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
import os from 'node:os';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || os.tmpdir();
const DECK = fs.readFileSync(new URL('../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json', import.meta.url), 'utf8').trim();

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 300s'); report(); process.exit(9); }, 300000).unref();

const WR = (lobby) => `${BASE}SharedUI/Sites/SWUSim/WaitingRoom.php?lobby=${lobby}`;

// The expected pinned width: clamp(180px, 18vw, 320px). At 1700 that is 306.
const expectedWidth = (vw) => Math.min(320, Math.max(180, Math.round(vw * 0.18)));

async function login(ctx, user) {
  await ctx.request.post(BASE + 'AccountFiles/AttemptPasswordLogin.php',
    { form: { submit: '1', userID: user, password: 'pass' } });
}

async function run(engineName, launcher) {
  const browser = await launcher.launch();
  try {
    // ── set up a real private room with two seated, logged-in players ──────────────────────────
    const c1 = await browser.newContext({ viewport: { width: 1700, height: 1050 } });
    const c2 = await browser.newContext({ viewport: { width: 1700, height: 1050 } });
    await login(c1, 'claudebot1');
    await login(c2, 'claudebot2');

    const host = await (await c1.request.post(BASE + 'APIs/Lobbies/JoinQueue.php',
      { form: { rootName: 'SWUSim', createPrivate: '1', format: 'twinsuns', deckLink: DECK } })).json();
    ok(engineName, 'created a private twinsuns room', !!host.success, host.message || '');
    if (!host.success) { await browser.close(); return; }
    const p2 = await (await c2.request.post(BASE + 'APIs/Lobbies/JoinQueue.php',
      { form: { rootName: 'SWUSim', privateInviteCode: host.inviteCode, deckLink: DECK } })).json();
    ok(engineName, 'a second seat joined', !!p2.success, p2.message || '');

    // The page reads its authKey from localStorage, exactly as a real join would have left it.
    const seat = async (ctx, lobby, key, playerID) => {
      const page = await ctx.newPage();
      await page.addInitScript(([l, k]) => {
        localStorage.setItem('tcg:lobbyAuth:' + l, JSON.stringify({ authKey: k, ts: Date.now() }));
      }, [lobby, key]);
      await page.goto(WR(lobby) + '&playerID=' + playerID);
      await page.waitForSelector('#tcg-chat-panel', { timeout: 15000 });
      return page;
    };
    const page1 = await seat(c1, host.lobbyID, host.authKey, host.playerID);
    const page2 = await seat(c2, host.lobbyID, p2.authKey, p2.playerID);

    // ── geometry ──────────────────────────────────────────────────────────────────────────────
    const w = await page1.evaluate(() => document.getElementById('tcg-chat-panel').offsetWidth);
    ok(engineName, `panel is 18% clamped (${expectedWidth(1700)}px)`, Math.abs(w - expectedWidth(1700)) <= 1, `got ${w}px`);

    // ── the panel HUGS ITS CONTENT (owner, 2026-09-22) ────────────────────────────────────────
    // A quiet room must NOT draw a full-height column; that was the "wasting precious UI
    // real-estate" report. It grows with the conversation and stops at the viewport.
    const empty = await page1.evaluate(() => {
      const r = document.getElementById('tcg-chat-panel').getBoundingClientRect();
      return { h: Math.round(r.height), left: Math.round(r.left), vh: window.innerHeight,
               placeholder: (document.getElementById('tcgc-stream').textContent || '').trim() };
    });
    ok(engineName, '★ an empty panel is a compact card, not a full-height column',
       empty.h < empty.vh * 0.45, `${empty.h}px of ${empty.vh}px`);
    ok(engineName, 'an empty panel is not a sliver either', empty.h >= 140, `${empty.h}px`);
    ok(engineName, 'an empty stream says so rather than looking broken',
       empty.placeholder === '' , `stream text: "${empty.placeholder}"`);   // text comes from ::after

    // ── the Petranaki HUD glass is actually painted ──────────────────────────────────────────
    // The skin lives in SWUSim's own override file, NOT in the shared component. If that file stops
    // matching the component's id the panel silently falls back to its neutral base and nobody
    // notices until a screenshot.
    const glass = await page1.evaluate(() => {
      const el = document.getElementById('tcg-chat-panel');
      const before = getComputedStyle(el, '::before');
      return { clip: before.clipPath || '', content: before.content,
               elBg: getComputedStyle(el).backgroundColor };
    });
    ok(engineName, '★ the glass layer is painted (::before has the chamfer clip)',
       /polygon/.test(glass.clip) && glass.content !== 'none', glass.clip.slice(0, 40));
    ok(engineName, 'the panel itself is transparent so the chamfer is not squared off',
       /rgba\(0, 0, 0, 0\)|transparent/.test(glass.elBg), glass.elBg);

    // ⚠ The roster grid is an EXPLICIT 2-track grid, not auto-fit. Narrowing the content area must
    // not silently make it 1x4 or 3-up — that is the bug the wr-grid comment warns about.
    const cols = await page1.evaluate(() => {
      const g = document.querySelector('.wr-grid');
      return g ? getComputedStyle(g).gridTemplateColumns.split(' ').length : -1;
    });
    ok(engineName, 'roster is still a 2-column grid beside the panel', cols === 2, `tracks=${cols}`);

    const noHScroll = await page1.evaluate(() => document.body.scrollWidth <= window.innerWidth + 1);
    ok(engineName, 'no horizontal page scroll at 1700px', noHScroll);

    // ⚠ THE COMPOSER MUST BE ON SCREEN. Caught by eye, not by assertion, on the first cut: the host
    // page has a nav bar above the flex row, so `top:0; height:100vh` overran the viewport by exactly
    // the header's height and cut the input and Send button off the bottom — with every other
    // assertion green. The panel now measures its own offset into --tcgc-top.
    const composerVisible = await page1.evaluate(() => {
      const c = document.getElementById('tcgc-composer').getBoundingClientRect();
      const p = document.getElementById('tcg-chat-panel').getBoundingClientRect();
      return { fits: c.bottom <= window.innerHeight + 1 && c.top >= 0 && c.height > 20,
               panelFits: p.bottom <= window.innerHeight + 1,
               cb: Math.round(c.bottom), pb: Math.round(p.bottom), vh: window.innerHeight };
    });
    ok(engineName, '★ the composer is fully on screen', composerVisible.fits,
       `composer bottom ${composerVisible.cb} / viewport ${composerVisible.vh}`);
    ok(engineName, '★ the panel does not overrun the viewport', composerVisible.panelFits,
       `panel bottom ${composerVisible.pb} / viewport ${composerVisible.vh}`);

    // And it must not slide under the site header once stuck.
    const clearsHeader = await page1.evaluate(() => {
      const nav = document.querySelector('.nav-bar-links, .home-header, header');
      if (!nav) return true;   // a page with no header has nothing to clear
      window.scrollTo(0, 400);
      const n = nav.getBoundingClientRect(), p = document.getElementById('tcg-chat-panel').getBoundingClientRect();
      window.scrollTo(0, 0);
      return n.bottom <= 0 || p.top >= n.bottom - 1;   // header scrolled away, or panel sits below it
    });
    ok(engineName, 'the stuck panel clears the site header', clearsHeader);

    // ── a message from each seat, with the right rail colour ──────────────────────────────────
    await page1.fill('#tcgc-input', 'hello from seat one');
    await page1.click('#tcgc-send');
    await page2.waitForFunction(() => document.querySelectorAll('#tcgc-stream .tcgc-row').length > 0, { timeout: 15000 });

    const rail = await page2.evaluate(() => {
      const row = document.querySelector('#tcgc-stream .tcgc-row');
      return { cls: row.className, colour: getComputedStyle(row).borderLeftColor, text: row.textContent };
    });
    ok(engineName, 'the message reached the OTHER seat', /hello from seat one/.test(rail.text), rail.text);
    ok(engineName, 'seat 1 rail is #6fb8ff', rail.colour === 'rgb(111, 184, 255)', rail.colour);
    ok(engineName, 'the row carries its seat class', /tcgc-p1/.test(rail.cls), rail.cls);

    // ── autoscroll: scrolled UP, an arriving message must NOT yank you down ───────────────────
    await page2.evaluate(() => {
      const s = document.getElementById('tcgc-stream');
      // Make the stream genuinely scrollable first, then park the reader at the top.
      for (let i = 0; i < 60; i++) {
        const d = document.createElement('div');
        d.className = 'tcgc-row tcgc-log';
        d.textContent = 'filler ' + i;
        s.appendChild(d);
      }
      s.scrollTop = 0;
    });
    await page1.fill('#tcgc-input', 'arriving while you read history');
    await page1.click('#tcgc-send');
    await page2.waitForTimeout(3500);
    const stayedPut = await page2.evaluate(() => document.getElementById('tcgc-stream').scrollTop === 0);
    ok(engineName, 'autoscroll does not yank a reader out of scrollback', stayedPut);

    // Having just stuffed 60 filler rows in, the card must have GROWN and then CAPPED at the
    // viewport rather than running off the bottom of the screen.
    const grown = await page2.evaluate(() => {
      const r = document.getElementById('tcg-chat-panel').getBoundingClientRect();
      return { h: Math.round(r.height), bottom: Math.round(r.bottom), vh: window.innerHeight };
    });
    ok(engineName, '★ the card grows with the conversation', grown.h > empty.h, `${empty.h} -> ${grown.h}px`);
    ok(engineName, '★ and caps at the viewport', grown.bottom <= grown.vh + 1,
       `bottom ${grown.bottom} / viewport ${grown.vh}`);

    await page1.screenshot({ path: `${SHOTS}/chatpanel-wr-${engineName}-desktop.png` });

    // ── guest: reads the stream, gets no composer ─────────────────────────────────────────────
    const cg = await browser.newContext({ viewport: { width: 1700, height: 1050 } });
    const guest = await seat(cg, host.lobbyID, host.authKey, host.playerID);
    await guest.waitForTimeout(2500);
    const guestComposer = await guest.evaluate(() => {
      const c = document.getElementById('tcgc-composer');
      return { hidden: getComputedStyle(c).display === 'none', note: document.getElementById('tcgc-note').textContent };
    });
    ok(engineName, 'a guest gets NO composer', guestComposer.hidden);
    ok(engineName, 'a guest is told why', /log in/i.test(guestComposer.note), guestComposer.note);
    await cg.close();

    // ── narrow: the drawer ────────────────────────────────────────────────────────────────────
    await page1.setViewportSize({ width: 390, height: 844 });
    await page1.waitForTimeout(400);
    const collapsed = await page1.evaluate(() => {
      const p = document.getElementById('tcg-chat-panel');
      const t = document.getElementById('tcg-chat-toggle');
      return { off: p.getBoundingClientRect().right <= 1, toggleShown: getComputedStyle(t).display !== 'none',
               hScroll: document.body.scrollWidth > window.innerWidth + 1 };
    });
    ok(engineName, 'at 390px the panel is off-screen', collapsed.off);
    ok(engineName, 'at 390px the 💬 toggle is shown', collapsed.toggleShown);
    ok(engineName, 'no horizontal page scroll at 390px', !collapsed.hScroll);

    await page1.click('#tcg-chat-toggle');
    await page1.waitForTimeout(400);
    const opened = await page1.evaluate(() => document.getElementById('tcg-chat-panel').getBoundingClientRect().left >= -1);
    ok(engineName, 'tapping the toggle slides the drawer in', opened);
    await page1.screenshot({ path: `${SHOTS}/chatpanel-wr-${engineName}-mobile.png` });

    await c1.request.post(BASE + 'APIs/Lobbies/LeaveQueue.php',
      { form: { rootName: 'SWUSim', lobbyID: host.lobbyID, playerID: String(host.playerID), authKey: host.authKey } });
    await c2.request.post(BASE + 'APIs/Lobbies/LeaveQueue.php',
      { form: { rootName: 'SWUSim', lobbyID: host.lobbyID, playerID: String(p2.playerID), authKey: p2.authKey } });
    await c1.close(); await c2.close();

    // ── the SIDEBOARD mount ───────────────────────────────────────────────────────────────────
    // A match whose game 1 is finished, with a known log carrying one line per seat.
    const sb = await makeSideboardFixture();
    if (!sb) { ok(engineName, 'sideboard fixture built', false, 'fixture endpoint failed'); return; }
    ok(engineName, 'sideboard fixture built', true, `match ${sb.matchId}`);

    const sbPage = async (seatNo, key) => {
      const ctx = await browser.newContext({ viewport: { width: 1700, height: 1050 } });
      await login(ctx, seatNo === 1 ? 'claudebot1' : 'claudebot2');
      const pg = await ctx.newPage();
      const errs = [];
      pg.on('pageerror', e => errs.push(String(e)));
      await pg.goto(`${BASE}SWUSim/Sideboard.php?matchId=${sb.matchId}&playerID=${seatNo}&authKey=${key}`);
      await pg.waitForSelector('.tcgc-loghead', { timeout: 20000 });
      return { ctx, pg, errs };
    };
    const s1 = await sbPage(1, 'sbk1');
    const s2 = await sbPage(2, 'sbk2');

    const sbView = async (o) => o.pg.evaluate(() => ({
      w: document.getElementById('tcg-chat-panel').offsetWidth,
      head: (document.querySelector('.tcgc-loghead') || {}).textContent || '',
      text: document.getElementById('tcgc-stream').textContent,
      composer: getComputedStyle(document.getElementById('tcgc-composer')).display,
      hScroll: document.body.scrollWidth > window.innerWidth + 1,
      cardsVisible: document.querySelectorAll('#deckGrid img').length
    }));
    const v1 = await sbView(s1), v2 = await sbView(s2);

    ok(engineName, 'sideboard panel is 18% clamped', Math.abs(v1.w - expectedWidth(1700)) <= 1, `got ${v1.w}px`);
    ok(engineName, 'sideboard shows the previous game log heading', /GAME 1 LOG/.test(v1.head), v1.head);
    // ⚠ An HTML ENTITY IN THE TITLE gets double-escaped — the first cut printed "GAME LOG &amp; CHAT"
    // on screen. RenderChatPanel htmlspecialchars() the title, so it must be given a plain "&".
    const label = await s1.pg.evaluate(() => document.querySelector('.tcgc-label').textContent);
    ok(engineName, 'the panel label is not double-escaped', label === 'GAME LOG & CHAT', label);
    ok(engineName, 'card ids render as NAMES, not ids', !/\[\[|SOR_\d|SEC_\d/.test(v1.text), v1.text.slice(0, 80));
    ok(engineName, 'the deck grid still renders beside the panel', v1.cardsVisible > 20, `${v1.cardsVisible} cards`);
    ok(engineName, 'no horizontal page scroll on the sideboard', !v1.hScroll);
    ok(engineName, 'a sideboard seat may chat', v1.composer === 'flex');

    // ★★ THE HIDDEN-INFORMATION CHECK. Game 1 is over, but games 2 and 3 are still to be played, so
    // what the opponent drew is exactly what sideboarding must not leak.
    ok(engineName, '★ seat 1 sees its own draw', /Capital City/.test(v1.text));
    ok(engineName, '★ seat 1 does NOT see seat 2 draw', !/Vanquish/.test(v1.text));
    ok(engineName, '★ seat 2 sees its own draw', /Vanquish/.test(v2.text));
    ok(engineName, '★ seat 2 does NOT see seat 1 draw', !/Capital City/.test(v2.text));

    // ⚠ THE NAME MAP IS ITS OWN LEAK VECTOR. GetGameLog returns a {id: title} map so the opponent's
    // cards can be named — if it were built from the RAW log rather than from the filtered lines, it
    // would hand back exactly the card the visibility filter had just removed.
    const names2 = await s2.pg.evaluate(async (mid) => {
      const r = await fetch(`./GetGameLog.php?matchId=${mid}&playerID=2&authKey=sbk2`);
      return Object.keys((await r.json()).names || {});
    }, sb.matchId);
    ok(engineName, '★ the name map does not name seat 1\'s hidden card',
       !names2.includes('SOR_020'), names2.join(','));

    ok(engineName, 'no page errors on the sideboard', s1.errs.length === 0, s1.errs.slice(0, 1).join(''));

    await s1.pg.screenshot({ path: `${SHOTS}/chatpanel-sb-${engineName}-desktop.png` });
    await s1.pg.setViewportSize({ width: 390, height: 844 });
    await s1.pg.waitForTimeout(400);
    const sbNarrow = await s1.pg.evaluate(() => ({
      off: document.getElementById('tcg-chat-panel').getBoundingClientRect().right <= 1,
      toggleShown: getComputedStyle(document.getElementById('tcg-chat-toggle')).display !== 'none',
      hScroll: document.body.scrollWidth > window.innerWidth + 1
    }));
    ok(engineName, 'sideboard at 390px: panel off-screen, toggle shown, no h-scroll',
       sbNarrow.off && sbNarrow.toggleShown && !sbNarrow.hScroll, JSON.stringify(sbNarrow));
    await s1.pg.screenshot({ path: `${SHOTS}/chatpanel-sb-${engineName}-mobile.png` });
    await s1.ctx.close(); await s2.ctx.close();
  } finally {
    await browser.close();
  }
}

// Builds a finished game 1 + a sideboarding match, via the local-dev fixture endpoints.
async function makeSideboardFixture() {
  const res = await fetch(BASE + 'DevTools/tdd-regression/fixtures/swusim_make_sideboard.php');
  const txt = await res.text();
  const m = txt.match(/matchId=([A-Za-z0-9_]+)/);
  return m ? { matchId: m[1] } : null;
}

for (const [name, launcher] of ENGINES) {
  try { await run(name, launcher); }
  catch (e) { allOk = false; results.push([name, 'ENGINE ERROR', false, String(e.message || e).slice(0, 200)]); }
}
report();
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
