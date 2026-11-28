// The log/chat column, sized to what it holds (owner, 2026-11-22):
//  • DESKTOP 4-seat table: the sidebar is only as wide as the whisper row — "Whisper: [P2] [P3] [P4]" on one line with the last chip
//    nearly touching the screen's right edge — and the boards get the rest. The width is ONE number (SWUTableLayout.sidebarW) that
//    both the CSS and the card-size formula read.
//  • PHONE (any board on the phone markup): the Message | Send line spans the whole chat column. #chatWidget's inline
//    max-width:calc(100vw - 156px) (its floating desktop mode) capped it at 234px on a 390px phone.
// Logged in as claudebot1 (guests get no composer).
//   node DevTools/ui-harness/swusim-chat-sidebar-fit-xbrowser.mjs     (ENGINES=chromium to narrow; SHOTS=<dir> for screenshots)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
const form = (o) => new URLSearchParams(o).toString();
const post = async (path, body, cookie) => fetch(BASE + path, { method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) }, body: form(body), redirect: 'manual' });
const login = await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' });
const cookies = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]);
const cookie = cookies.join('; ');
async function seed(schema) {
  const json = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php', { schema }, cookie)).text());
  if (json.error) throw new Error(`TestSchemaSetup refused: ${json.error}`);
  return String(json.gameName);
}
const read = (f) => fs.readFileSync(new URL('../../SWUSim/Tests/Visual/' + f, import.meta.url), 'utf8');
const url = (g, seat) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=${seat}&authKey=testschema`;
const TS4 = read('TwinSuns_4P_AllZonesFilled.md');
const TWO = read('BaseHealthBar_1v1.md');

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };

const measure = (p) => p.evaluate(() => {
  const r = (el) => { if (!el) return null; const b = el.getBoundingClientRect(); return { x: b.left, y: b.top, w: b.width, h: b.height, r: b.right, b: b.bottom }; };
  const wr = document.getElementById('swuWhisperRow');
  const send = document.querySelector('#chatWidgetControls button');
  const hdr = document.getElementById('swuSidebarHeader');
  return {
    W: document.documentElement.clientWidth, cardSize: window.cardSize, cfg: window.SWUTableLayout || null,
    sidebar: r(document.getElementById('swuSidebar')), mount: r(document.getElementById('swuChatMount')),
    composer: r(document.getElementById('chatComposer')), input: r(document.getElementById('chatText')), send: r(send),
    chips: wr ? [...wr.querySelectorAll('label')].map(l => ({ t: l.textContent.trim(), ...r(l) })) : [],
    head: wr ? r(wr.querySelector('.swu-whisper-head')) : null,
    header: hdr ? [...hdr.querySelectorAll('*')].filter(e => e.getBoundingClientRect().width > 0).map(e => ({ id: e.id || e.className, ...r(e) })) : [],
  };
});

async function open(b, engine, g, seat, vp, phone) {
  const ctx = await b.newContext({ viewport: vp, ...(phone ? { userAgent: IPHONE_UA, isMobile: engine !== 'firefox', hasTouch: true } : {}) });
  await ctx.addCookies(cookies.map(c => { const i = c.indexOf('='); return { name: c.slice(0, i), value: c.slice(i + 1), url: BASE }; }));
  const p = await ctx.newPage();
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(url(g, seat), { waitUntil: 'domcontentloaded' });
  await p.waitForFunction(() => !!document.getElementById('chatText') && document.querySelectorAll('[id*="GroundArena-"]').length > 0, null, { timeout: 60000 }).catch(() => {});
  await p.waitForTimeout(1500);
  return { ctx, p, errs };
}

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  const g4 = await seed(TS4), g2 = await seed(TWO);

  // ── Desktop 4-seat table ──
  for (const vp of [{ width: 2000, height: 1150 }, { width: 1600, height: 1000 }, { width: 1366, height: 768 }]) {
    for (const seat of [1, 4]) {
      const name = `${engine}/desktop${vp.width}/P${seat}`;
      const { ctx, p, errs } = await open(b, engine, g4, seat, vp, false);
      const m = await measure(p);
      const sbW = m.cfg && m.cfg.sidebarW;
      check(Number.isInteger(sbW) && sbW > 0 && sbW < 280, name, `the table publishes ONE sidebar width, narrower than the old 280 (SWUTableLayout.sidebarW = ${sbW})`);
      check(!!m.sidebar && Math.abs(m.sidebar.w - sbW) <= 0.5 && Math.abs(m.sidebar.r - m.W) <= 1, name, `the sidebar is that wide, flush right: ${JSON.stringify(m.sidebar)}`);
      const colW = (vp.width - sbW) / 2;
      const want = Math.max(36, Math.min(80, Math.floor((colW - 24 - 28) / 7)));
      check(m.cardSize === want, name, `card size follows the sidebar: ${m.cardSize} = max(36, min(80, floor(((${vp.width}-${sbW})/2-52)/7))) = ${want}`);
      check(m.chips.length === 3, name, `three whisper chips: ${JSON.stringify(m.chips.map(c => c.t))}`);
      if (m.chips.length && m.head) {
        check(m.chips.every(c => Math.abs(c.y - m.chips[0].y) <= 1) && Math.abs(m.head.y + m.head.h / 2 - (m.chips[0].y + m.chips[0].h / 2)) <= 2, name,
          `"Whisper:" and its chips on ONE line: ${JSON.stringify([m.head.y, ...m.chips.map(c => c.y)])}`);
        const last = m.chips[m.chips.length - 1];
        const gap = m.W - last.r;
        // ≤ 14: the row is left-aligned, so its end moves with the labels — seat 4's "P1" is ~2px narrower than seat 1's "P4".
        check(gap >= 2 && gap <= 14, name, `the last chip (${last.t}) nearly touches the screen's right edge: ${gap.toFixed(1)}px`);
      }
      for (const e of m.header) check(e.r <= m.sidebar.r + 0.5 && e.x >= m.sidebar.x - 0.5, name, `header item ${e.id} stays inside the sidebar: ${JSON.stringify([e.x, e.r])}`);
      if (m.send && m.composer) check(m.send.r <= m.sidebar.r + 0.5 && m.composer.x >= m.sidebar.x, name, 'Message | Send stays inside the sidebar');
      check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
      if (SHOTS && seat === 1) await p.screenshot({ path: `${SHOTS}/chat-desktop${vp.width}-${engine}.png` });
      await ctx.close();
    }
  }

  // ── Phone: 4-seat table and a two-player board ──
  for (const [g, label] of [[g4, '4p'], [g2, '2p']]) {
    const name = `${engine}/phone390/${label}`;
    const { ctx, p, errs } = await open(b, engine, g, 1, { width: 390, height: 844 }, true);
    // The chat lives at the end of the page on a phone; bring it on screen so the measurement is of a laid-out composer.
    await p.evaluate(() => { const t = document.getElementById('chatText'); if (t) t.scrollIntoView({ block: 'center' }); });
    await p.waitForTimeout(300);
    const m = await measure(p);
    check(!!m.mount && !!m.composer && !!m.send, name, 'the composer is on the page');
    if (m.mount && m.composer && m.send) {
      check(Math.abs(m.composer.x - m.mount.x) <= 1 && Math.abs(m.send.r - m.mount.r) <= 1, name,
        `Message | Send spans the chat column: composer ${m.composer.x.toFixed(0)}..send ${m.send.r.toFixed(0)} vs column ${m.mount.x.toFixed(0)}..${m.mount.r.toFixed(0)}`);
      check(m.input.w > m.send.w * 2, name, `the message box takes the room, not Send: input ${m.input.w.toFixed(0)} send ${m.send.w.toFixed(0)}`);
    }
    check(errs.length === 0, name, `no page errors: ${errs.slice(0, 2).join(' | ')}`);
    if (SHOTS) await p.screenshot({ path: `${SHOTS}/chat-phone-${label}-${engine}.png` });
    await ctx.close();
  }
  await b.close();
}
console.log(fails === 0 ? `PASS (${checks} checks)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
