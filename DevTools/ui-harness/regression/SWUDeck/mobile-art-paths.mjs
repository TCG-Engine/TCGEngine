// Regression: every CLIENT-built art URL on the mobile board comes from the shared corpus seam.
//
// The identity banner (leader/base art) and the "recently added" strip build their URLs in JS. They
// used to build them from window.rootPath ("./SWUDeck") — the per-app art trees deleted in the
// 2026-08-05 shared-corpus migration — so both silently 404'd: a blank banner and blank thumbnails,
// on a page whose server-rendered tiles were all fine. Server-side paths already go through
// SWUCardImagePath(); this pins the CLIENT half to window.swuCardArtUrl (its JS twin).
//
// Asserting the URL PREFIX is not enough — a correct-looking path can still 404 — so every image is
// also required to have decoded (naturalWidth > 0).
//
// MUTATES THE DECK (adding cards is how the recent strip populates): runs against scratch deck
// 900001 and restores its Gamestate afterwards. Never point GAME at a real deck.
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { ENGINES, login, openBoard, mobileContextOpts, harness, EnvError } from '../lib.mjs';

const GAME = process.env.GAME || '900001';
const REPO = new URL('../../../../', import.meta.url).pathname;
const STATE = `${REPO}SWUDeck/Games/${GAME}/Gamestate.txt`;
const only = (process.env.ENGINES || 'chromium,firefox').split(',').map(s => s.trim()).filter(Boolean);
const SUITE_ENGINES = Object.fromEntries(only.map(n => [n, ENGINES[n]]));
// Any request to an app-owned art tree is the bug this suite exists to catch.
const PER_APP_ART = /\/(SWUDeck|SWUSim|SWUCardList|AzukiDeck)\/(concat|crops|WebpImages)\//i;

if (!existsSync(STATE)) throw new EnvError(`scratch deck ${GAME} missing (${STATE}) — see library-scroll-persistence.mjs`);

await harness(async (check) => {
  for (const [name, engine] of Object.entries(SUITE_ENGINES)) {
    if (!engine) { check(`${name}: engine available`, false, 'unknown engine name'); continue; }
    console.log(`\n=== ${name} ===`);
    const snapshot = readFileSync(STATE);
    const browser = await engine.launch();
    try {
      const page = await (await browser.newContext(mobileContextOpts())).newPage();
      const perApp = [];
      page.on('request', r => { if (PER_APP_ART.test(r.url())) perApp.push(r.url()); });

      await login(page);
      await openBoard(page, { game: GAME, mobile: true });

      check(`${name}: art seam emitted on the board`,
        await page.evaluate(() => typeof window.swuCardArtUrl) === 'function');
      const root = await page.evaluate(() => window.SWUArtRoot);
      check(`${name}: art root is the shared corpus`, root === '/TCGEngine/AppCore/SWU/Images', String(root));

      // ── Identity banner: leader art uses the deployed "_back" crop, base its own ──────────────
      await page.evaluate(() => window.SWUDeckMobileSetPane && window.SWUDeckMobileSetPane('deck'));
      await page.waitForTimeout(2500);
      const ident = await page.evaluate(() => ['swuMobileLeaderArt', 'swuMobileBaseArt'].map(id => {
        const i = document.querySelector('#' + id + ' img');
        return i ? { id, src: i.getAttribute('src') || '', w: i.naturalWidth } : { id, src: '', w: 0 };
      }));
      for (const a of ident) {
        check(`${name}: ${a.id} uses the shared corpus`, a.src.startsWith('/TCGEngine/AppCore/SWU/Images/'), a.src);
        check(`${name}: ${a.id} actually decoded`, a.w > 0, `naturalWidth ${a.w}`);
      }

      // ── Recently-added strip ──────────────────────────────────────────────────────────────────
      await page.evaluate(() => window.SWUDeckMobileSetPane && window.SWUDeckMobileSetPane('search'));
      await page.evaluate(() => {
        const t = [...document.querySelectorAll('.panelTab')].find(x => x.textContent.trim() === 'Cards');
        if (t) t.click();
      });
      await page.waitForFunction(() => !!document.querySelector('#my_CardPane_content #myCards'), null, { timeout: 15000 });
      await page.waitForTimeout(1000);
      await page.evaluate(() => {
        const a = [...document.querySelectorAll("#my_CardPane_content #myCards > span[data-mzid]")]
          .filter(el => { const r = el.getBoundingClientRect();
                          return r.width > 20 && r.top > 60 && r.bottom < window.innerHeight - 60; })[2];
        if (a) a.setAttribute('data-tapme', '1');
      });
      await page.locator('[data-tapme]').tap();
      await page.waitForTimeout(2500);

      const thumbs = await page.evaluate(() => [...document.querySelectorAll('.swu-mobile-recent-card img')]
        .map(i => ({ src: i.getAttribute('src') || '', w: i.naturalWidth })));
      check(`${name}: a card was added to the recent strip`, thumbs.length > 0, `${thumbs.length} thumb(s)`);
      check(`${name}: recent thumbs use the shared corpus`,
        thumbs.every(t => t.src.startsWith('/TCGEngine/AppCore/SWU/Images/')),
        thumbs.map(t => t.src).join(' '));
      check(`${name}: recent thumbs actually decoded`, thumbs.every(t => t.w > 0),
        thumbs.map(t => t.w).join(','));

      // The catch-all: nothing on this page may request an app-owned art tree at all.
      check(`${name}: no per-app art tree requested`, perApp.length === 0,
        perApp.slice(0, 3).join(' '));
    } finally {
      await browser.close();
      writeFileSync(STATE, snapshot);
    }
  }
});
