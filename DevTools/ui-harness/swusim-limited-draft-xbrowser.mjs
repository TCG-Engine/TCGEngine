// SWUSim Limited Draft — the pick screen (SWUSim/LimitedDraft.php). Chromium + Firefox + WebKit, desktop (1440x900) and
// phone width (390x844). Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.2; checklist
// SWUSim/Tests/Visual/LimitedDraft_NewPetranakiHud.md. Rooms: SWUSim/DevTools/tests/limited_fixture.php?mode=draft&players=3
// &phase=drafting (the leader draft just started; claudebot1 is seat 1).
//   · the top bar says "Leader draft · Pick 1 of 3" with a m:ss clock; the pod row shows 3 seats with you marked;
//   · a click selects (outlined) and names the Pick button; confirming moves on and your picks count rises by 1;
//   · a fast double confirm never picks twice; the aspect chart has 6 bars;
//   · 390px: no sideways scroll, the picks bar sits under the pack and opens on tap; desktop: a 600 ms hover previews;
//   · every button is ≥44px.
// Usage: node swusim-limited-draft-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

setTimeout(() => { console.log('WATCHDOG: harness took over 15 minutes'); process.exit(2); }, 900000).unref();
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

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const where = `${ename} ${size.name}`;
    try {
      const api = await browser.newContext();
      const fx = await (await api.request.get(`${BASE}SWUSim/DevTools/tests/limited_fixture.php?mode=draft&players=3&phase=drafting`)).json();
      if (!fx.lobbyID) throw new Error('fixture failed: ' + JSON.stringify(fx));
      const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
      await ctx.addInitScript(([l, k]) => { try { localStorage.setItem('tcg:lobbyAuth:' + l, JSON.stringify({ authKey: k, ts: Date.now() })); } catch (e) {} }, [fx.lobbyID, fx.keys[1]]);
      const page = await ctx.newPage();
      await page.goto(`${BASE}${fx.urls.draft}`, { waitUntil: 'domcontentloaded', timeout: T });
      await page.waitForSelector('#ldPack .ld-card', { timeout: T });

      const title = await page.textContent('#ldTitle');
      check(where, /Leader draft · Pick 1 of 3/.test(title) && /^\d+:\d\d$/.test((await page.textContent('#ldClock')).trim()), 'the top bar names the leader draft, pick 1 of 3, and a clock', title);
      const pod = await page.$$eval('#ldPod .ld-seat', (s) => s.map((x) => [x.textContent.trim(), x.classList.contains('ld-seat--me')]));
      check(where, pod.length === 3 && pod.filter(([, me]) => me).length === 1, 'the pod row shows 3 seats, you marked once', JSON.stringify(pod));
      check(where, (await page.$$('#ldLean .ld-bar')).length === 6, 'the aspect chart has 6 bars');

      // Final review: an unchanged pack is not rebuilt between polls (a rebuild drops keyboard focus and can eat a click).
      const kept = await page.evaluate(async () => {
        const n = document.querySelector('#ldPack .ld-card'); n.__mark = 1;
        await new Promise((r) => setTimeout(r, 3500));   // two polls
        const m = document.querySelector('#ldPack .ld-card');
        return !!m && m.__mark === 1;
      });
      check(where, kept, 'the pack is not rebuilt while nothing changes');
      const picks0 = parseInt(await page.textContent('#ldPickCount'), 10);
      const first = page.locator('#ldPack .ld-card').first();
      const firstTitle = await first.getAttribute('title');
      await first.click();
      const sel = await page.evaluate(() => { const c = document.querySelector('#ldPack .ld-card--sel'); return c ? c.getAttribute('title') : ''; });
      const btn = (await page.textContent('#ldPick')).trim();
      check(where, sel === firstTitle && btn.startsWith('Pick ') && btn.length > 5, 'a click selects the card and names the Pick button', btn);
      // A fast double confirm: the second click must not pick again.
      await page.locator('#ldPick').dblclick();
      await page.waitForFunction((n) => parseInt(document.getElementById('ldPickCount').textContent, 10) > n, picks0, { timeout: T });
      await page.waitForTimeout(2500);
      const picks1 = parseInt(await page.textContent('#ldPickCount'), 10);
      check(where, picks1 === picks0 + 1, 'confirming picks exactly once, even on a double click', `${picks0} → ${picks1}`);
      check(where, /Waiting/.test(await page.textContent('#ldStatus')), 'with nothing in front, the status says it is waiting');

      // Owner (2026-11-05): every seat's drafted leaders are public. Seat 2 keeps a leader through the API; my panel shows it.
      const s2 = await (await api.request.post(`${BASE}SWUSim/LimitedDraftAction.php`, { form: { lobbyID: fx.lobbyID, authKey: fx.keys[2], action: 'state' } })).json();
      await api.request.post(`${BASE}SWUSim/LimitedDraftAction.php`, { form: { lobbyID: fx.lobbyID, authKey: fx.keys[2], action: 'pick', packId: String(s2.front.packId), cardId: s2.front.cards[0] } });
      if (size.name === 'phone') {
        check(where, !(await page.locator('#ldPodLeadsBody').isVisible()), '390px: "Leaders drafted" starts folded');
        await page.locator('#ldPodLeadsToggle').click();
      }
      await page.waitForFunction(() => document.querySelectorAll('#ldPodLeads .ld-pl-row').length === 3 && document.querySelectorAll('#ldPodLeads .ld-plead').length >= 2, null, { timeout: T }).catch(() => {});
      const pl = await page.$$eval('#ldPodLeads .ld-pl-row', (rs) => rs.map((r) => [r.querySelector('.ld-pl-name').textContent.trim(), r.querySelectorAll('.ld-plead').length]));
      const mine = pl.find(([n]) => n === 'You'), theirs = pl.filter(([n]) => n !== 'You');
      check(where, pl.length === 3 && mine && mine[1] === 1 && theirs.some(([, k]) => k === 1), '"Leaders drafted" lists all 3 seats: mine, and the leader seat 2 just kept', JSON.stringify(pl));
      const lead = page.locator('#ldPodLeads .ld-plead').first();
      check(where, !!(await lead.getAttribute('title')), 'each leader thumbnail is named (title)');
      if (size.name === 'desktop') {
        await lead.hover(); await page.waitForTimeout(700);
        check(where, await page.evaluate(() => { const p = document.getElementById('ldPreview'); return !!p && p.style.display === 'block'; }), 'hovering a drafted leader previews it full size');
        await page.mouse.move(5, 5); await page.waitForTimeout(100);
      }

      const over = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      check(where, over <= 0, 'no horizontal page scroll', String(over));
      const short = await page.evaluate(() => [...document.querySelectorAll('.ld-root button')].filter((b) => b.offsetParent !== null)
        .map((b) => [b.textContent.trim(), Math.round(b.getBoundingClientRect().height)]).filter(([, h]) => h < 44));
      check(where, short.length === 0, 'every button is at least 44px tall', JSON.stringify(short));
      if (size.name === 'phone') {
        const pos = await page.evaluate(() => { const p = document.getElementById('ldPack').getBoundingClientRect(), s = document.getElementById('ldSide').getBoundingClientRect(); return { pack: p.bottom, side: s.top }; });
        check(where, pos.side >= pos.pack - 1, 'the picks bar sits under the pack', JSON.stringify(pos));
        await page.locator('#ldSideToggle').click();
        check(where, await page.locator('#ldLean').isVisible(), 'tapping the bar opens the picks panel');
      } else {
        // a fresh pack to hover: the fixture's seat 1 has none now — reload another room
        const fx2 = await (await api.request.get(`${BASE}SWUSim/DevTools/tests/limited_fixture.php?mode=draft&players=3&phase=drafting`)).json();
        await page.evaluate(([l, k]) => localStorage.setItem('tcg:lobbyAuth:' + l, JSON.stringify({ authKey: k, ts: Date.now() })), [fx2.lobbyID, fx2.keys[1]]);
        await page.goto(`${BASE}${fx2.urls.draft}`, { waitUntil: 'domcontentloaded', timeout: T });
        await page.waitForSelector('#ldPack .ld-card', { timeout: T });
        await page.locator('#ldPack .ld-card').first().hover();
        await page.waitForTimeout(700);
        check(where, await page.evaluate(() => { const p = document.getElementById('ldPreview'); return !!p && p.style.display === 'block'; }), 'a 600 ms hover shows the full-size preview');
      }
      await shot(page, `limited-draft-${ename}-${size.name}`);
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
  }
  await browser.close();
}
console.log(failures === 0 ? 'ALL PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
