// Hovering a LEADER previews BOTH faces, side by side (owner, 2026-10-01; the approach of SWUniversity's
// src/components/Shared/CardPreview.tsx). Core/jsInclude.js: CardDetailLeaderFaces / ShowLeaderFacesDetail.
//
// The board seeds three cases, because each one breaks a different way:
//   · their leader in its LEADER ZONE — TWI_017 Chancellor Palpatine, whose BACK is another LANDSCAPE leader face
//     (Darth Sidious): orientation cannot be assumed "front landscape, back portrait";
//   · my DEPLOYED leader unit — ASH_009 Ahsoka Tano, whose tile is "<CardID>_back" (the portrait unit side): the
//     same pair must come back, FRONT FIRST, whichever face was hovered;
//   · a leader attached as a PILOT — JTL_001 Asajj Ventress on my Alliance X-Wing: its strip renders the "_back"
//     face and is previewed by ShowSubcardDetail, a different function from every other card;
//   · an ordinary unit (SOR_095) — the control: still ONE image, so the pair is not on for everything.
// Pinned: the two faces share their SHORT EDGE (one card scale — a landscape front's height equals a portrait
// back's width), sit side by side on desktop and fit the window. The phone (touch long-press) STACKS them and
// shows a close X but no flip button — both sides are already showing.
//   node DevTools/ui-harness/swusim-leader-both-faces-xbrowser.mjs        (seeds its own board)
// Screenshots: $SHOTS (default /tmp/leader-faces).
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '/tmp/leader-faces';
fs.mkdirSync(SHOTS, { recursive: true });
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };
const want = (n, c, m) => c ? ok() : bad(n, m);

const SCHEMA = [
  '## GIVEN',
  'CommonSetup: rrk/bbw/{myLeader:ASH_009:1:1; theirLeader:TWI_017}',
  'SkipPreGame: true',
  'WithGamePhase: ActionPhase',
  'WithActivePlayer: 1',
  'WithP2GroundArena: SOR_095:1:0',
  'WithP1SpaceArena: SOR_237:1:0',
  'WithP1SpaceArenaPilot: 0:JTL_001',
  '## WHEN',
  '## EXPECT',
].join('\n');
const form = (o) => new URLSearchParams(o).toString();
async function seedBoard() {
  const lr = await fetch(BASE + 'AccountFiles/AttemptPasswordLogin.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: form({ submit: '1', userID: 'claudebot1', password: 'pass' }), redirect: 'manual',
  });
  const cookie = (lr.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
  if (!cookie) throw new Error('no session cookie for claudebot1');
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', cookie },
    body: form({ schema: SCHEMA }), redirect: 'manual',
  });
  const j = JSON.parse(await r.text());
  if (j.error) throw new Error(`TestSchemaSetup refused the schema: ${j.error}`);
  return String(j.gameName);
}
let GAME = process.env.GAME;
if (!GAME) {
  try { GAME = await seedBoard(); console.log(`seeded board ${GAME}`); }
  catch (e) { console.log(`FAIL :: could not seed a board :: ${e.message}`); process.exit(1); }
}

// What #cardDetail shows right now.
const detail = (p) => p.evaluate(() => {
  const el = document.getElementById('cardDetail');
  if (!el || el.style.display === 'none') return { open: false };
  const imgs = [...el.querySelectorAll('img')].map(i => {
    const r = i.getBoundingClientRect();
    return { src: (i.getAttribute('src') || '').split('/').pop(), x: r.left, y: r.top, w: r.width, h: r.height, right: r.right, bottom: r.bottom };
  });
  return { open: true, pair: !!el.querySelector('[data-card-detail-pair]'), imgs, vw: innerWidth, vh: innerHeight,
           controls: [...el.querySelectorAll('[data-card-detail-control]')].map(c => c.getAttribute('data-card-detail-control')) };
});
const shortEdge = (i) => Math.min(i.w, i.h);
const hoverImg = async (p, srcPart) => {
  const loc = p.locator(`img[src*="/${srcPart}."]`).first();
  if (!(await loc.count())) return false;
  await p.mouse.move(2, 2); await p.waitForTimeout(150);
  await loc.hover({ force: true });
  await p.waitForTimeout(1500);   // SWUSim's hover dwell is 400ms, then a fade
  return true;
};

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  // ── desktop: hover ──────────────────────────────────────────────────────────────────────────
  {
    const n = `${engine}/desktop`;
    const p = await b.newPage({ viewport: { width: 1600, height: 1000 } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${GAME}&playerID=1&authKey=testschema`, { waitUntil: 'domcontentloaded' });
    await p.waitForTimeout(2500);
    want(n, await p.evaluate(() => typeof Cardtype === 'function'), 'the board has the client card dictionary (Cardtype)');

    // 1. their leader in its zone — double-sided, both faces landscape
    if (!(await hoverImg(p, 'TWI_017'))) bad(n, 'no TWI_017 leader tile on the board');
    else {
      const d = await detail(p);
      want(n, d.open && d.pair && d.imgs.length === 2, `leader zone: a two-face preview (${JSON.stringify(d.imgs?.map(i => i.src))})`);
      if (d.imgs?.length === 2) {
        const [f, k] = d.imgs;
        want(n, /^TWI_017\.webp/.test(f.src) && /^TWI_017_back\.webp/.test(k.src), `front first, then back: ${f.src}, ${k.src}`);
        want(n, f.w > f.h && k.w > k.h, `Palpatine's back (Sidious) is LANDSCAPE too: ${Math.round(k.w)}x${Math.round(k.h)}`);
        want(n, Math.abs(shortEdge(f) - shortEdge(k)) <= 1.5, `one card scale — short edges ${shortEdge(f)} / ${shortEdge(k)}`);
        want(n, k.x >= f.right - 1, 'side by side: the back sits to the right of the front');
        want(n, Math.min(f.x, k.x) >= 0 && Math.max(f.right, k.right) <= d.vw + 1 && Math.max(f.bottom, k.bottom) <= d.vh + 1, 'the pair fits the window');
      }
      await p.screenshot({ path: `${SHOTS}/${engine}-desktop-leader-zone.png` });
    }

    // 2. my deployed leader UNIT — its tile is the "_back" face; the pair is the same, front first
    if (!(await hoverImg(p, 'ASH_009_back'))) bad(n, 'no deployed ASH_009 unit (ASH_009_back tile) on the board');
    else {
      const d = await detail(p);
      want(n, d.open && d.pair && d.imgs.length === 2, `deployed leader unit: a two-face preview (${JSON.stringify(d.imgs?.map(i => i.src))})`);
      if (d.imgs?.length === 2) {
        const [f, k] = d.imgs;
        want(n, /^ASH_009\.webp/.test(f.src) && /^ASH_009_back\.webp/.test(k.src), `front first even though the BACK was hovered: ${f.src}, ${k.src}`);
        want(n, f.w > f.h && k.h > k.w, `landscape leader front, portrait unit back: ${Math.round(f.w)}x${Math.round(f.h)}, ${Math.round(k.w)}x${Math.round(k.h)}`);
        want(n, Math.abs(f.h - k.w) <= 1.5, `one card scale — front height ${f.h} = back width ${k.w}`);
        want(n, k.x >= f.right - 1, 'side by side');
        want(n, Math.max(f.h, k.h) <= 401, `desktop keeps the single-card 400px ceiling: tallest ${Math.max(f.h, k.h)}`);
      }
      await p.screenshot({ path: `${SHOTS}/${engine}-desktop-deployed.png` });
    }

    // 3. a leader PILOT — its strip is an <img data-subcard-id> previewed by ShowSubcardDetail
    const pilot = p.locator('img[data-subcard-id="JTL_001"]').first();
    if (!(await pilot.count())) bad(n, 'no JTL_001 pilot strip on the X-Wing');
    else {
      await p.mouse.move(2, 2); await p.waitForTimeout(150);
      await pilot.hover({ force: true }); await p.waitForTimeout(1500);
      const d = await detail(p);
      want(n, d.open && d.pair && d.imgs.length === 2, `leader pilot: a two-face preview (${JSON.stringify(d.imgs?.map(i => i.src))})`);
      if (d.imgs?.length === 2) {
        const [f, k] = d.imgs;
        want(n, /^JTL_001\.webp/.test(f.src) && /^JTL_001_back\.webp/.test(k.src), `pilot: front first, then back: ${f.src}, ${k.src}`);
        want(n, Math.abs(Math.min(f.w, f.h) - Math.min(k.w, k.h)) <= 1.5, 'pilot: one card scale');
      }
      await p.screenshot({ path: `${SHOTS}/${engine}-desktop-pilot.png` });
    }

    // 4. control: an ordinary unit stays one image
    const unitSrc = await p.evaluate(() => { const i = [...document.images].find(x => /SOR_095/.test(x.src)); return i ? i.src.split('/').pop().replace(/\..*$/, '') : null; });
    if (!unitSrc || !(await hoverImg(p, unitSrc))) bad(n, 'no SOR_095 unit tile on the board (the control)');
    else {
      const d = await detail(p);
      want(n, d.open && !d.pair && d.imgs.length === 1, `a non-leader stays ONE image (pair ${d.pair}, ${d.imgs?.length} imgs)`);
    }
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await p.close();
  }
  // ── phone: the touch long-press path (Playwright cannot synthesise the native gesture, so the
  //    long-press's own synthetic event is handed to ShowDetail exactly as BeginCardDetailLongPress does) ──
  {
    const n = `${engine}/phone`;
    const p = await b.newPage({ viewport: { width: 390, height: 844 } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${GAME}&playerID=1&authKey=testschema&swuLayout=mobile`, { waitUntil: 'domcontentloaded' });
    await p.waitForTimeout(2500);
    const opened = await p.evaluate(() => {
      const img = [...document.images].find(x => /ASH_009_back\./.test(x.src));
      if (!img) return false;
      ShowDetail({ type: 'touchlongpress', clientX: 195, clientY: 400, target: img }, img.src);
      return true;
    });
    if (!opened) bad(n, 'no deployed ASH_009 tile on the phone board');
    else {
      await p.waitForTimeout(1500);
      const d = await detail(p);
      want(n, d.open && d.pair && d.imgs.length === 2, `touch: a two-face preview (${JSON.stringify(d.imgs?.map(i => i.src))})`);
      if (d.imgs?.length === 2) {
        const [f, k] = d.imgs;
        want(n, k.y >= f.bottom - 1, 'a portrait phone STACKS the faces (back under front)');
        want(n, Math.abs(f.h - k.w) <= 1.5, `one card scale — front height ${f.h} = back width ${k.w}`);
        want(n, Math.min(f.x, k.x) >= 0 && Math.max(f.right, k.right) <= d.vw + 1 && Math.max(f.bottom, k.bottom) <= d.vh + 1, 'the stack fits the phone screen');
      }
      want(n, d.controls.includes('close') && !d.controls.includes('flip'), `a close X and no flip button: ${JSON.stringify(d.controls)}`);
      await p.screenshot({ path: `${SHOTS}/${engine}-phone-deployed.png` });
    }
    // the pilot strip's long-press goes through ShowSubcardDetail, as BeginCardDetailLongPress routes it
    await p.evaluate(() => { if (typeof HideCardDetail === 'function') HideCardDetail(true); });
    const pil = await p.evaluate(() => {
      const img = document.querySelector('img[data-subcard-id="JTL_001"]');
      if (!img) return false;
      ShowSubcardDetail({ type: 'touchlongpress', clientX: 195, clientY: 400, target: img }, img, { allowTouch: true, skipDelay: true });
      return true;
    });
    if (!pil) bad(n, 'no JTL_001 pilot strip on the phone board');
    else {
      await p.waitForTimeout(1500);
      const d = await detail(p);
      want(n, d.open && d.pair && d.imgs.length === 2 && /^JTL_001\.webp/.test(d.imgs[0].src), `touch pilot: the two-face preview (${JSON.stringify(d.imgs?.map(i => i.src))})`);
    }
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await p.close();
  }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} leader-faces checks failed` : `\nLEADER BOTH FACES OK — ${checks} checks, 3 engines (shots in ${SHOTS})`);
process.exit(fails ? 1 : 0);
