// Regression: the deck-image modal (main menu → Generate Image) has a "Copy Link" button that copies the
// SAME friendly link as the deck list menu's "Copy Link" (/deck/<code>, or the NextTurn fallback for a deck
// with no code yet) — so someone sharing a deck can grab both the image and the link from one window.
// Added 2026-09-11.
//
// What each check locks down:
//   • SAME LINK — black-box: copy via the deck list menu, paste back; copy via the modal, paste back; the two
//     must be identical (and a /deck/<code> link when the deck has a friendly code).
//   • IN THE CLICK TURN — the clipboard call must be issued inside the click's user activation, the property
//     WebKit (every iOS browser) enforces and Playwright's WebKit does not (see mobile-clipboard.mjs).
//   • ONE TRUE FLASH — exactly one "Link copied!".
//   • THE FLASH IS VISIBLE — it used to be drawn at z-index 3000, UNDER the modal's 5000 overlay, so the
//     modal's copy confirmations ("Deck image copied!" too) were invisible. elementFromPoint at the flash's
//     centre must hit the flash itself.
//   • PHONE WIDTH — all three modal buttons stay inside the viewport (the row wraps).
import { ENGINES, BASE, EnvError, harness, mobileContextOpts, desktopContextOpts, login } from '../lib.mjs';

async function instrument(page) {
  await page.evaluate(() => {
    window.__flashes = [];
    window.__clipboardCalls = [];
    let inClickTurn = false;
    document.addEventListener('click', () => {
      inClickTurn = true;
      setTimeout(() => { inClickTurn = false; }, 0);
    }, true);
    const origFlash = window.showFlashMessage;
    window.showFlashMessage = function (msg, ev) {
      window.__flashes.push(msg);
      const r = origFlash.call(this, msg, ev);
      // Measure visibility NOW, while the 900 ms flash is still up.
      const el = [...document.querySelectorAll('body > div')].reverse().find(d => d.innerText === msg);
      if (el) {
        const b = el.getBoundingClientRect();
        const hit = document.elementFromPoint(b.left + b.width / 2, b.top + b.height / 2);
        window.__flashVisible = !!hit && (hit === el || el.contains(hit));
        window.__flashBox = { top: b.top, left: b.left, w: b.width, h: b.height, vw: innerWidth, vh: innerHeight };
      }
      return r;
    };
    if (navigator.clipboard && navigator.clipboard.write) {
      const orig = navigator.clipboard.write.bind(navigator.clipboard);
      navigator.clipboard.write = function (items) { window.__clipboardCalls.push({ api: 'clipboard.write', inClickTurn }); return orig(items); };
    }
    const origExec = document.execCommand.bind(document);
    document.execCommand = function (cmd, ...rest) {
      if (cmd === 'copy') window.__clipboardCalls.push({ api: 'execCommand', inClickTurn });
      return origExec(cmd, ...rest);
    };
  });
}
const resetProbes = (page) => page.evaluate(() => { window.__flashes = []; window.__clipboardCalls = []; window.__flashVisible = undefined; });
const probes = (page) => page.evaluate(() => ({ flashes: window.__flashes, calls: window.__clipboardCalls, visible: window.__flashVisible, box: window.__flashBox }));

async function pasteBack(page) {
  await page.evaluate(() => {
    let ta = document.getElementById('__pasteProbe');
    if (!ta) {
      ta = document.createElement('textarea');
      ta.id = '__pasteProbe';
      ta.style.cssText = 'position:fixed;bottom:0;left:0;width:200px;height:60px;z-index:99999;';
      document.body.appendChild(ta);
    }
    ta.value = '';
    ta.focus();
  });
  await page.focus('#__pasteProbe');
  await page.keyboard.press('ControlOrMeta+V');
  await page.waitForTimeout(250);
  return page.evaluate(() => document.getElementById('__pasteProbe').value);
}

// Prime with a sentinel via the known-good synchronous pattern, so a failed round-trip indicts the harness.
async function primeClipboard(page, sentinel) {
  await page.evaluate((text) => {
    let b = document.getElementById('__primeBtn');
    if (!b) {
      b = document.createElement('button');
      b.id = '__primeBtn';
      b.style.cssText = 'position:fixed;bottom:70px;left:0;z-index:99999;';
      document.body.appendChild(b);
    }
    b.onclick = function () {
      const ta = document.createElement('textarea');
      ta.value = text; document.body.appendChild(ta); ta.select();
      document.execCommand('copy'); document.body.removeChild(ta);
    };
  }, sentinel);
  await page.click('#__primeBtn', { force: true });
  await page.waitForTimeout(150);
  const got = await pasteBack(page);
  if (got !== sentinel) throw new EnvError(`clipboard round-trip failed (got ${JSON.stringify(got)}) — harness cannot read the clipboard here`);
}

// A deck, preferring one with a friendly code (the /deck/<code> case is the one users share). Read from the
// page's own SWU_DECK_CODES map and the rows' "Generate Image" buttons, which exist in BOTH layouts.
async function pickDeck(page) {
  const decks = await page.$$eval("button[title='Generate Image']", (btns) =>
    btns.map((b) => {
      const m = /GenerateDeckImage\(\s*"([^"]+)"/.exec(b.getAttribute('onclick') || '');
      return m ? { deckID: m[1], code: (window.SWU_DECK_CODES || {})[m[1]] || '' } : null;
    }).filter(Boolean));
  if (!decks.length) throw new EnvError('no deck rows on the main menu — does Drixx own any decks?');
  return decks.find(d => d.code) || decks[0];
}

// The deck LIST's own entry points differ by layout: desktop rows carry inline icon buttons ("Copy Link"
// opens a small copy-options menu), the mobile layout hides those behind the row's ⋮ dropdown.
async function listCopyLink(page, layout, deckID) {
  if (layout === 'desktop') {
    await page.locator(`button[title='Copy Link'][onclick*='"${deckID}"']`).first().click();
    const opt = page.locator('body > div button', { hasText: /^Copy Link$/ }).first();
    await opt.waitFor({ timeout: 5000 }).catch(() => { throw new EnvError('desktop copy-options menu never opened'); });
    await opt.click();
  } else {
    await mobileMenuItem(page, deckID, 'Copy Link');
  }
}
async function listGenerateImage(page, layout, deckID) {
  if (layout === 'desktop') await page.locator(`button[title='Generate Image'][onclick*='"${deckID}"']`).first().click();
  else await mobileMenuItem(page, deckID, 'Generate Image');
}
async function mobileMenuItem(page, deckID, label) {
  await page.locator(`.deck-more-btn[onclick*='"${deckID}"']`).first().click();
  await page.waitForSelector('#deckDropdownMenu', { timeout: 5000 });
  const item = page.locator('#deckDropdownMenu button', { hasText: label }).first();
  if (!(await item.count())) throw new EnvError(`"${label}" missing from the deck dropdown`);
  await item.click();
}

harness(async (check) => {
  for (const [engine, type] of Object.entries(ENGINES)) {
    for (const layout of ['desktop', 'mobile']) {
      console.log(`\n=== ${engine} / ${layout} ===`);
      const browser = await type.launch();
      const extra = engine === 'chromium' ? { permissions: ['clipboard-read', 'clipboard-write'] } : {};
      const ctx = await browser.newContext(layout === 'mobile' ? mobileContextOpts(extra) : desktopContextOpts(extra));
      const page = await ctx.newPage();
      try {
        await login(page);
        const resp = await page.goto(`${BASE}/SharedUI/Sites/SWUDeck/MainMenu.php`, { waitUntil: 'domcontentloaded' });
        if (resp && resp.status() >= 400) throw new EnvError(`MainMenu HTTP ${resp.status()}`);
        await page.waitForSelector("button[title='Generate Image']", { state: 'attached', timeout: 15000 })
          .catch(() => { throw new EnvError('main menu never rendered a deck row'); });
        const row = await pickDeck(page);
        console.log(`   (deck ${row.deckID}${row.code ? `, code ${row.code}` : ', no friendly code'})`);
        await instrument(page);

        // 1. The reference: the deck list menu's Copy Link.
        await primeClipboard(page, `SENTINEL-${engine}-${layout}-menu`);
        await listCopyLink(page, layout, row.deckID);
        await page.waitForTimeout(300);
        const menuLink = (await pasteBack(page)).trim();
        if (!/^https?:\/\/\S+$/.test(menuLink)) throw new EnvError(`the deck list's Copy Link produced ${JSON.stringify(menuLink)} — reference unusable`);

        // 2. Open the image modal.
        await listGenerateImage(page, layout, row.deckID);
        try { await page.waitForSelector('#deckImageModalOverlay', { timeout: 90000 }); }
        catch { throw new EnvError('deck image never rendered — CreateImage.php slow or failing (needs vendor/ + card art)'); }

        // textContent, not innerText: the site's button CSS uppercases the RENDERED text ("COPY IMAGE").
        const labels = await page.$$eval('#deckImageModalOverlay button', bs => bs.map(b => b.textContent.trim()));
        check(`[${layout}] modal shows Copy Image, Copy Link, Close in that order`,
          JSON.stringify(labels.filter(l => ['Copy Image', 'Copy Link', 'Close'].includes(l))) === JSON.stringify(['Copy Image', 'Copy Link', 'Close']),
          JSON.stringify(labels));
        const offscreen = await page.$$eval('#deckImageModalOverlay button', bs => bs.filter(b => {
          const r = b.getBoundingClientRect(); return r.left < 0 || r.right > innerWidth + 0.5;
        }).map(b => b.textContent.trim()));
        check(`[${layout}] every modal button fits inside the viewport`, offscreen.length === 0, JSON.stringify(offscreen));

        // 3. The modal's Copy Link.
        await primeClipboard(page, `SENTINEL-${engine}-${layout}-modal`);
        await resetProbes(page);
        await page.locator('#deckImageModalOverlay button', { hasText: 'Copy Link' }).first().click();
        await page.waitForTimeout(300);
        const { flashes, calls, visible, box } = await probes(page);
        const modalLink = (await pasteBack(page)).trim();

        check(`[${layout}] modal Copy Link copies the same link as the deck list's Copy Link`, modalLink === menuLink,
          `modal ${JSON.stringify(modalLink)} vs menu ${JSON.stringify(menuLink)}`);
        if (row.code) check(`[${layout}] it is the friendly /deck/<code> link`, modalLink.endsWith(`/deck/${row.code}`), modalLink);
        check(`[${layout}] the copy is issued inside the click turn`, calls.length > 0 && calls.every(c => c.inClickTurn), JSON.stringify(calls));
        check(`[${layout}] exactly one "Link copied!" flash`, flashes.length === 1 && flashes[0] === 'Link copied!', JSON.stringify(flashes));
        check(`[${layout}] the flash is drawn ON TOP of the modal and on screen`,
          visible === true && box && box.top >= 0 && box.top + box.h <= box.vh,
          JSON.stringify({ visible, box }));
      } finally {
        await browser.close();
      }
    }
  }
});
