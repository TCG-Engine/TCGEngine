// SWUSim Limited deckbuilder (SWUSim/LimitedBuilder.php) — pool = sideboard, ×0 greyed, search + filters, Submit → Ready
// with editing re-arming Submit, the cost curve, the phone Pool | Deck switch, the hover preview, and a reload keeping the
// draft. Chromium + Firefox + WebKit, desktop (1440x900) and phone width (390x844). Spec: docs/superpowers/specs/2026-11-04-
// swusim-limited-sealed-draft-design.md §3.4; checklist SWUSim/Tests/Visual/LimitedBuilder_NewPetranakiHud.md.
//
// Rooms come from SWUSim/DevTools/tests/limited_fixture.php?phase=building (6 SOR packs each; nobody has submitted) and
// ?phase=sideboard (a Bo3 after game 1 — the between-games builder, authenticated by the match seat's key in the URL).
//
// Usage: node swusim-limited-builder-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

setTimeout(() => { console.log('WATCHDOG: harness took over 20 minutes'); process.exit(2); }, 1200000).unref();
const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const ONLY  = (process.argv[4] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
const SIZES = [{ name: 'desktop', w: 1440, h: 900 }, { name: 'phone', w: 390, h: 844 }];
const T = 45000;

let failures = 0;
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);
const check = (where, cond, msg, detail = '') => cond ? ok(where, msg) : bad(where, msg + (detail ? ' — ' + detail : ''));
const shot = async (page, name) => { if (SHOTS) await page.screenshot({ path: `${SHOTS}/${name}.png`, fullPage: true }); };

async function fixture(request, phase) {
  const r = await request.get(`${BASE}SWUSim/DevTools/tests/limited_fixture.php?phase=${phase}`);
  const j = await r.json();
  if (!j.lobbyID) throw new Error('fixture failed: ' + JSON.stringify(j));
  return j;
}
async function seatContext(browser, size, fx, seat) {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  await ctx.addInitScript(([lobbyID, key]) => {
    try { localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey: key, ts: Date.now() })); } catch (e) {}
  }, [fx.lobbyID, fx.keys[seat]]);
  return ctx;
}
const counts = (page) => page.evaluate(() => ({
  left: parseInt(document.getElementById('lbCardsLeft').textContent, 10),
  deck: parseInt(document.getElementById('lbDeckCount').textContent, 10),
  tiles: document.querySelectorAll('#lbPoolGrid .lb-card').length,
}));
const qtyOf = (page, id, grid = '#lbPoolGrid') => page.evaluate(([id, grid]) => {
  const n = document.querySelector(`${grid} .lb-card[data-id="${id}"] .lb-qty`); return n ? parseInt(n.textContent.replace(/\D/g, ''), 10) : null;
}, [id, grid]);
const showPane = async (page, size, which) => { if (size.name === 'phone') await page.locator(`label[for="lb-view-${which}"]`).click(); };
const pickTab = (page, tab) => page.locator(`label[for="lb-tab-${tab}"]`).click();

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const tag = `${ename} ${size.name}`;
    let where = `${tag} build`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'building');
      const ctx = await seatContext(browser, size, fx, 1);
      const page = await ctx.newPage();
      await page.goto(`${BASE}${fx.urls.builder}`, { waitUntil: 'domcontentloaded', timeout: T });
      await page.waitForSelector('#lbPoolGrid .lb-card', { timeout: T });

      // 1. Cards left = every non-leader, non-base pool card: 6 packs × 14, less any rare base an SOR rare slot opened.
      const c0 = await counts(page);
      const qtySum = await page.$$eval('#lbPoolGrid .lb-card .lb-qty', (q) => q.reduce((n, x) => n + parseInt(x.textContent.replace(/\D/g, ''), 10), 0));
      check(where, c0.left === qtySum && c0.left >= 80 && c0.left <= 84 && c0.deck === 0, 'Cards (n left) counts every non-leader, non-base pool card', JSON.stringify([c0, qtySum]));

      // 2. Add one copy of a ×2 card, then send it back.
      const two = await page.evaluate(() => { const q = [...document.querySelectorAll('#lbPoolGrid .lb-card')].find((b) => /×2$/.test((b.querySelector('.lb-qty') || {}).textContent || '')); return q ? q.getAttribute('data-id') : ''; });
      await page.locator(`#lbPoolGrid .lb-card[data-id="${two}"]`).click();
      check(where, two !== '' && (await qtyOf(page, two)) === 1 && (await counts(page)).deck === 1, 'a ×2 card becomes ×1 and the deck +1', two);
      await showPane(page, size, 'deck');
      await page.locator(`#lbDeckGrid .lb-card[data-id="${two}"]`).click();
      await showPane(page, size, 'pool');
      check(where, (await qtyOf(page, two)) === 2 && (await counts(page)).deck === 0, 'clicking the deck copy sends it back');

      // 3. A used-up card stays visible, greyed.
      const one = await page.evaluate(() => { const q = [...document.querySelectorAll('#lbPoolGrid .lb-card')].find((b) => /×1$/.test((b.querySelector('.lb-qty') || {}).textContent || '')); return q.getAttribute('data-id'); });
      await page.locator(`#lbPoolGrid .lb-card[data-id="${one}"]`).click();
      const used = await page.evaluate((id) => { const n = document.querySelector(`#lbPoolGrid .lb-card[data-id="${id}"]`); return n ? parseFloat(getComputedStyle(n).opacity) : -1; }, one);
      check(where, used >= 0 && used <= 0.35, 'a card at ×0 stays in the pool, greyed', String(used));

      // 4. Search and an aspect toggle narrow the grid; clearing restores it.
      const all = (await counts(page)).tiles;
      await page.fill('#lbSearch', 'a');
      const searched = (await counts(page)).tiles;
      await page.locator('.lb-tog[data-f="aspect"]').first().click();
      const narrowed = (await counts(page)).tiles;
      check(where, searched <= all && narrowed < searched, 'search, then an aspect toggle, narrow the pool', `${all} → ${searched} → ${narrowed}`);
      await page.locator('.lb-tog[data-f="aspect"]').first().click();
      await page.fill('#lbSearch', '');
      check(where, (await counts(page)).tiles === all, 'clearing the filters restores the pool');

      // Groups come in the game's aspect order, single aspects before their pairs, aspect-less last.
      const ORDER = ['Vigilance', 'Command', 'Aggression', 'Cunning', 'Heroism', 'Villainy'];
      const groups = await page.$$eval('#lbPoolGrid .lb-group', (h) => h.map((x) => x.textContent.trim()));
      const keyOf = (g) => g.toLowerCase() === 'no aspect' ? [99] : g.split(' · ').map((a) => ORDER.findIndex((o) => o.toLowerCase() === a.toLowerCase()));
      const cmp = (a, b) => { for (let i = 0; i < Math.max(a.length, b.length); i++) { if (a[i] === undefined) return -1; if (b[i] === undefined) return 1; if (a[i] !== b[i]) return a[i] - b[i]; } return 0; };
      check(where, groups.length > 1 && groups.every((g, i) => i === 0 || cmp(keyOf(groups[i - 1]), keyOf(g)) < 0), 'the pool is grouped by aspect, in order', JSON.stringify(groups));
      // Two aspect toggles are ALL OF: every group left carries both.
      await page.locator('.lb-tog[data-f="aspect"][data-v="Vigilance"]').click();
      await page.locator('.lb-tog[data-f="aspect"][data-v="Heroism"]').click();
      const both = await page.$$eval('#lbPoolGrid .lb-group', (h) => h.map((x) => x.textContent.trim().toLowerCase()));
      check(where, both.every((g) => g.includes('vigilance') && g.includes('heroism')), 'Vigilance + Heroism shows only cards with both', JSON.stringify(both));
      await page.locator('.lb-tog[data-f="aspect"][data-v="Vigilance"]').click();
      await page.locator('.lb-tog[data-f="aspect"][data-v="Heroism"]').click();
      check(where, (await counts(page)).tiles === all, 'and turning them off restores the pool');

      // 6. The curve moves when a card is added.
      const curve = () => page.$$eval('#lbCurve .lb-bar-fill', (b) => b.map((x) => x.style.height).join(','));
      const before = await curve();
      // 5. Leader, base, then 30 cards → Submit → Ready; an edit in between re-arms Submit.
      await pickTab(page, 'leaders'); await page.locator('#lbPoolGrid .lb-card').first().click();
      await pickTab(page, 'bases');   await page.locator('#lbPoolGrid .lb-card').first().click();
      await pickTab(page, 'cards');
      while ((await counts(page)).deck < 30) {
        const next = await page.evaluate(() => { const b = [...document.querySelectorAll('#lbPoolGrid .lb-card:not(.lb-card--used)')][0]; return b ? b.getAttribute('data-id') : ''; });
        if (!next) break;
        await page.locator(`#lbPoolGrid .lb-card[data-id="${next}"]`).click();
      }
      check(where, (await curve()) !== before, 'the cost curve changes as cards are added');
      check(where, (await counts(page)).deck === 30 && !(await page.locator('#lbSubmit').isDisabled()), 'with a leader, a base and 30 cards, Submit is enabled');
      check(where, await page.locator('#lbReady').isDisabled(), 'Ready waits for Submit');
      await page.locator('#lbSubmit').click();
      await page.waitForFunction(() => !document.getElementById('lbReady').disabled, null, { timeout: T });
      ok(where, 'after Submit, Ready is enabled');
      await shot(page, `limited-builder-${ename}-${size.name}-submitted`);
      const any = await page.evaluate(() => document.querySelector('#lbPoolGrid .lb-card:not(.lb-card--used)').getAttribute('data-id'));
      await page.locator(`#lbPoolGrid .lb-card[data-id="${any}"]`).click();
      check(where, !(await page.locator('#lbSubmit').isDisabled()) && (await page.locator('#lbReady').isDisabled()), 'an edit after Submit re-enables Submit and disables Ready');

      // 9. A reload keeps the draft (31 cards, unsubmitted).
      await page.reload({ waitUntil: 'domcontentloaded' });
      await page.waitForSelector('#lbPoolGrid .lb-card', { timeout: T });
      await page.waitForFunction(() => document.getElementById('lbDeckCount').textContent === '31', null, { timeout: T }).catch(() => {});
      check(where, (await counts(page)).deck === 31, 'a reload brings the draft back', String((await counts(page)).deck));

      await showPane(page, size, 'deck');
      await page.locator(`#lbDeckGrid .lb-card[data-id="${any}"]`).click();
      await showPane(page, size, 'pool');
      // Back to the 30 that were submitted: Submit has nothing new to send and Ready is offered again.
      check(where, await page.locator('#lbSubmit').isDisabled(), 'undoing the edit returns to the submitted deck (Submit off)');
      await page.waitForFunction(() => !document.getElementById('lbReady').disabled, null, { timeout: T });
      await page.locator('#lbReady').click();
      await page.locator('.sd-ok').click({ timeout: T });   // the StyledConfirm "Lock in this deck?"
      await page.waitForFunction(() => document.body.classList.contains('lb-is-ready'), null, { timeout: T });
      const lockedBefore = (await counts(page)).deck;
      const another = await page.evaluate(() => { const b = document.querySelector('#lbPoolGrid .lb-card:not(.lb-card--used)'); return b ? b.getAttribute('data-id') : ''; });
      if (another) await page.locator(`#lbPoolGrid .lb-card[data-id="${another}"]`).click({ force: true });
      check(where, (await counts(page)).deck === lockedBefore, 'after Ready, card clicks change nothing');

      // 7. Phone: no sideways scroll; the footer's buttons stay on screen after scrolling the pool.
      const over = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      check(where, over <= 0, 'no horizontal page scroll', String(over));
      if (size.name === 'phone') {
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
        const vis = await page.evaluate(() => { const r = document.getElementById('lbReady').getBoundingClientRect(); return r.top >= 0 && r.bottom <= window.innerHeight; });
        check(where, vis, 'the footer (Submit / Ready) stays on screen while the pool scrolls');
      } else {
        // 8. A 600 ms hover shows the preview.
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.locator('#lbPoolGrid .lb-card').first().hover();
        await page.waitForTimeout(700);
        const shown = await page.evaluate(() => { const p = document.getElementById('lbPreview'); return !!p && p.style.display === 'block'; });
        check(where, shown, 'hovering a card for 600 ms shows its full-size preview');
      }
      await shot(page, `limited-builder-${ename}-${size.name}-ready`);
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── between games of a Bo3 ──────────────────────────────────────────────────────────────────────────────────────
    where = `${tag} match`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'sideboard');
      const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
      const page = await ctx.newPage();
      await page.goto(`${BASE}${fx.urls.builder}`, { waitUntil: 'domcontentloaded', timeout: T });
      await page.waitForSelector('#lbPoolGrid .lb-card', { timeout: T });
      const st = await page.evaluate(() => ({ deck: parseInt(document.getElementById('lbDeckCount').textContent, 10), ready: !document.getElementById('lbReady').disabled,
                                              timer: document.getElementById('lbTimer').textContent }));
      check(where, st.deck === 30 && st.ready && /^\d:\d\d left$/.test(st.timer), 'opens on the last game\'s 30 cards, Ready available, a 5-minute clock', JSON.stringify(st));
      await pickTab(page, 'leaders');
      const leaders = await page.locator('#lbPoolGrid .lb-card').count();
      check(where, leaders >= 2, 'other leaders from the pool can be chosen', String(leaders));
      await shot(page, `limited-builder-${ename}-${size.name}-match`);
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
  }
  await browser.close();
}
console.log(failures === 0 ? 'ALL PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
