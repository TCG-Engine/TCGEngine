// SWUDeck: a LEADER previews BOTH faces, side by side (owner 2026-10-01), on the two SWUDeck surfaces asked for:
//   · the LEFT CARD PANEL (Leaders / Leader1 / Leader2 tabs: WebpImages tiles; Cards tab: concat tiles);
//   · the IDENTITY BANNER above it — the faded "splash" leader + base art. It was pointer-events:none so a stray
//     click could not fire the slot's Remove(myLeader); its card links now take HOVER again while every click in
//     the banner is swallowed in the capture phase (SWUDeck/Custom/GameLayout.php guardIdentityBannerClicks).
// Shared code: Core/jsInclude.js CardDetailLeaderFaces / ShowLeaderFacesDetail (gate: SWUSim + SWUDeck).
// Pinned: the pair (front first, one card scale, beside the hovered tile — not over it); an ordinary card and a BASE
// stay one image; a click on the banner leader fires NO ZoneClickHandler and the leader stays in the deck; the phone
// long-press STACKS the pair. Chromium / Firefox / WebKit.
//   node DevTools/ui-harness/swudeck-leader-both-faces-xbrowser.mjs     (needs the SWUDeck stack on :3100 and the
//   Drixx login; imports its own deck from swudb — or set DECK=<gameName> to reuse one)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
const BASE = (process.env.BASE || 'http://localhost:3100/TCGEngine').replace(/\/$/, '');
const SHOTS = process.env.SHOTS || '/tmp/swudeck-leader-faces';
const DECKLINK = process.env.DECKLINK || 'https://swudb.com/deck/eeFFtweXI';
fs.mkdirSync(SHOTS, { recursive: true });
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };
const want = (n, c, m) => c ? ok() : bad(n, m);

async function login(ctx) {
  const r = await ctx.request.post(`${BASE}/AccountFiles/AttemptPasswordLogin.php`, { form: { submit: '1', userID: 'Drixx', password: 'pass' }, maxRedirects: 0 });
  if (r.status() !== 302) throw new Error(`login as Drixx: HTTP ${r.status()}`);
}
let DECK = process.env.DECK;
if (!DECK) {
  const b = await chromium.launch(); const ctx = await b.newContext();
  try {
    await login(ctx);
    const r = await ctx.request.get(`${BASE}/SWUDeck/CreateDeck.php?deckLink=${encodeURIComponent(DECKLINK)}&format=premier`, { maxRedirects: 0 });
    DECK = (/gameName=(\d+)/.exec(r.headers()['location'] || '') || [])[1];
    if (!DECK) throw new Error(`CreateDeck gave no deck (HTTP ${r.status()}, location ${r.headers()['location']})`);
    console.log(`imported deck ${DECK}`);
  } catch (e) { console.log(`FAIL :: environment :: ${e.message}`); process.exit(1); }
  await b.close();
}

const detail = (p) => p.evaluate(() => {
  const el = document.getElementById('cardDetail');
  if (!el || el.style.display === 'none') return { open: false };
  const imgs = [...el.querySelectorAll('img')].map(i => {
    const r = i.getBoundingClientRect();
    return { src: (i.getAttribute('src') || '').split('/').pop(), x: r.left, y: r.top, w: r.width, h: r.height, right: r.right, bottom: r.bottom };
  });
  return { open: true, pair: !!el.querySelector('[data-card-detail-pair]'), imgs, vw: innerWidth, vh: innerHeight };
});
// Aim at the tile's top-left CORNER, not its centre: a Cards-tab tile carries the +/- widget buttons over its
// centre (a sibling of the card link, so hovering them is not hovering the card) — as a player's pointer would.
const hover = async (p, loc) => {
  await p.mouse.move(2, 2); await p.waitForTimeout(200);
  await loc.hover({ force: true, position: { x: 8, y: 8 } }); await p.waitForTimeout(1400);
};
const pairOK = (n, d, id, what) => {
  want(n, d.open && d.pair && d.imgs.length === 2, `${what}: a two-face preview (${JSON.stringify(d.imgs?.map(i => i.src))})`);
  if (d.imgs?.length !== 2) return;
  const [f, k] = d.imgs;
  want(n, f.src.startsWith(id + '.webp') && k.src.startsWith(id + '_back.webp'), `${what}: front first, then back: ${f.src}, ${k.src}`);
  want(n, Math.abs(Math.min(f.w, f.h) - Math.min(k.w, k.h)) <= 1.5, `${what}: one card scale (short edges ${Math.min(f.w, f.h)} / ${Math.min(k.w, k.h)})`);
  want(n, k.x >= f.right - 1, `${what}: side by side`);
  want(n, Math.min(f.x, k.x) >= 0 && Math.max(f.right, k.right) <= d.vw + 1 && Math.max(f.bottom, k.bottom) <= d.vh + 1, `${what}: fits the window`);
};

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  // ── desktop ──
  {
    const n = `${engine}/desktop`;
    const ctx = await b.newContext({ viewport: { width: 1600, height: 1000 } }); await login(ctx);
    const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}/NextTurn.php?gameName=${DECK}&playerID=1&folderPath=SWUDeck`, { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => document.querySelectorAll("#myCardPaneSlot a[onmouseover*='ShowCardDetail']").length > 0, null, { timeout: 20000 });
    await p.waitForTimeout(800);

    // 1. card panel, Leaders tab: the first leader tile
    const tile = p.locator("#myCardPaneSlot a[onmouseover*='ShowCardDetail']").first();
    const tileID = (await tile.locator('img').getAttribute('src')).split('/').pop().replace(/\.webp.*$/, '');
    await hover(p, tile);
    let d = await detail(p);
    pairOK(n, d, tileID, `card panel leader ${tileID}`);
    if (d.imgs?.length === 2) {
      const tr = await tile.boundingBox();
      const overlaps = d.imgs.some(i => i.x < tr.x + tr.width && i.right > tr.x && i.y < tr.y + tr.height && i.bottom > tr.y);
      want(n, !overlaps, 'card panel: the pair sits BESIDE the hovered tile, not over it');
    }
    await p.screenshot({ path: `${SHOTS}/${engine}-desktop-cardpanel.png` });

    // 2. identity banner: leader (its art is the Leader Unit CROP) and base
    const bannerLeader = p.locator("#swuIdentityBanner #myLeaderSlot a[onmouseover*='ShowCardDetail']").first();
    const leaderID = ((await bannerLeader.locator('img').getAttribute('src')) || '').split('/').pop().replace(/(_back)?_cropped\..*$/, '');
    await hover(p, bannerLeader);
    d = await detail(p);
    pairOK(n, d, leaderID, `identity banner leader ${leaderID}`);
    await p.screenshot({ path: `${SHOTS}/${engine}-desktop-banner.png` });
    const bannerBase = p.locator("#swuIdentityBanner #myBaseSlot a[onmouseover*='ShowCardDetail']").first();
    await hover(p, bannerBase);
    d = await detail(p);
    want(n, d.open && !d.pair && d.imgs.length === 1, `identity banner base: ONE card (pair ${d.pair}, ${d.imgs?.length} imgs)`);

    // 3. a banner CLICK must still do nothing — the reason the banner was pointer-events:none
    await p.evaluate(() => { window.__zoneClicks = 0; const f = window.ZoneClickHandler; window.ZoneClickHandler = function () { window.__zoneClicks++; return f && f.apply(this, arguments); }; });
    await bannerLeader.click({ force: true });
    await p.waitForTimeout(1500);
    const after = await p.evaluate(() => ({ clicks: window.__zoneClicks, leader: [...document.querySelectorAll('#myLeaderSlot img')].map(i => i.getAttribute('src')).join(',') }));
    want(n, after.clicks === 0, `a click on the banner leader fires no ZoneClickHandler (fired ${after.clicks})`);
    want(n, after.leader.includes(leaderID), `the leader is still in the deck after the click: ${after.leader}`);

    // 4. control: Cards tab, an ordinary (non-leader) card stays ONE image
    await p.locator('text=Cards').first().click();
    await p.waitForTimeout(1500);
    const unit = await p.evaluate(() => {
      const a = [...document.querySelectorAll("#myCardPaneSlot a[onmouseover*='ShowCardDetail']")].find(x => {
        const id = (x.querySelector('img')?.getAttribute('src') || '').split('/').pop().replace(/\.webp.*$/, '');
        return id && String(Cardtype(id) || '').indexOf('Leader') === -1;
      });
      if (!a) return null; a.setAttribute('data-probe-unit', '1'); return true;
    });
    if (!unit) bad(n, 'no non-leader tile in the Cards tab');
    else {
      await hover(p, p.locator('[data-probe-unit]').first());
      d = await detail(p);
      want(n, d.open && !d.pair && d.imgs.length === 1, `Cards tab non-leader: ONE card (pair ${d.pair}, ${d.imgs?.length} imgs)`);
    }
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await ctx.close();
  }
  // ── phone: the long-press on a card-panel leader (the synthetic event BeginCardDetailLongPress hands ShowDetail) ──
  {
    const n = `${engine}/phone`;
    const ctx = await b.newContext({ viewport: { width: 390, height: 844 } }); await login(ctx);
    const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}/NextTurn.php?gameName=${DECK}&playerID=1&folderPath=SWUDeck&swuLayout=mobile`, { waitUntil: 'domcontentloaded' });
    await p.waitForTimeout(4000);
    const id = await p.evaluate(() => {
      const img = [...document.querySelectorAll("a[onmouseover*='ShowCardDetail'] img")].find(i => {
        const x = (i.getAttribute('src') || '').split('/').pop().replace(/(_back)?\.webp.*$/, '');
        return String(Cardtype(x) || '').indexOf('Leader') !== -1;
      });
      if (!img) return null;
      ShowDetail({ type: 'touchlongpress', clientX: 195, clientY: 400, target: img }, img.src);
      return (img.getAttribute('src') || '').split('/').pop().replace(/(_back)?\.webp.*$/, '');
    });
    if (!id) bad(n, 'no leader tile on the phone layout');
    else {
      await p.waitForTimeout(1500);
      const d = await detail(p);
      want(n, d.open && d.pair && d.imgs.length === 2, `touch: a two-face preview of ${id} (${JSON.stringify(d.imgs?.map(i => i.src))})`);
      if (d.imgs?.length === 2) {
        const [f, k] = d.imgs;
        want(n, k.y >= f.bottom - 1, 'a portrait phone STACKS the faces');
        want(n, Math.min(f.x, k.x) >= 0 && Math.max(f.right, k.right) <= d.vw + 1 && Math.max(f.bottom, k.bottom) <= d.vh + 1, 'the stack fits the phone');
      }
      await p.screenshot({ path: `${SHOTS}/${engine}-phone.png` });
    }
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await ctx.close();
  }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} SWUDeck leader-faces checks failed` : `\nSWUDECK LEADER BOTH FACES OK — ${checks} checks, 3 engines, deck ${DECK} (shots in ${SHOTS})`);
process.exit(fails ? 1 : 0);
