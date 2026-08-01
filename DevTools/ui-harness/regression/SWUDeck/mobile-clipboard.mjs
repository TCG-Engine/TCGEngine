// Regression: the main menu's deck "Copy Text" / "Copy JSON" / "Copy Image" must actually reach
// the clipboard — on WebKit (i.e. EVERY iOS browser: Safari, Brave, Chrome) as well as Chromium
// and Firefox.
//
// The bug this locks down: all three fetched their payload and then copied from an async
// continuation (an XHR callback for the text exports, an awaited jpeg->png canvas conversion for
// the image). WebKit only permits a clipboard write that STARTS inside the click handler's
// user-activation window, so `document.execCommand('copy')` returned false and
// `navigator.clipboard.write` was refused — nothing was copied. Chromium and Firefox don't
// enforce that, which is why it looked fine on desktop; and it's why "Copy Link" / "Copy Karabast
// Import Link", which copy synchronously, always worked.
//
// TWO LAYERS OF ASSERTION, deliberately:
//   1. Black-box — copy, then paste back and compare. The real symptom. Self-validating: each case
//      first primes the clipboard with a unique sentinel via the known-good synchronous pattern,
//      so a failed round-trip indicts the HARNESS (EnvError) rather than reporting a regression.
//   2. White-box — the clipboard call must be issued in the same task as the click. This is the
//      property WebKit actually enforces, and it is the one Playwright's WebKit build does NOT
//      enforce for `navigator.clipboard.write` (verified: awaiting first still succeeds here while
//      failing on real iOS). Without this check the image fix would be untested by automation, and
//      a future "just await the blob first" refactor would silently re-break iOS with all green.
//
// Layer 2 is why this suite is trustworthy for a device bug it cannot literally reproduce.
import { ENGINES, BASE, EnvError, harness, mobileContextOpts, login } from '../lib.mjs';

// Record (a) every flash message the page raises and (b) every clipboard call plus whether it was
// issued while the click's user activation was still live. A capturing click listener opens the
// window and a setTimeout(0) closes it — which is exactly when the browser's own transient
// activation for clipboard purposes lapses.
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
    window.showFlashMessage = function (msg, ev) { window.__flashes.push(msg); return origFlash.call(this, msg, ev); };

    if (navigator.clipboard && navigator.clipboard.write) {
      const origWrite = navigator.clipboard.write.bind(navigator.clipboard);
      navigator.clipboard.write = function (items) {
        window.__clipboardCalls.push({ api: 'clipboard.write', inClickTurn });
        return origWrite(items);
      };
    }
    const origExec = document.execCommand.bind(document);
    document.execCommand = function (cmd, ...rest) {
      if (cmd === 'copy') window.__clipboardCalls.push({ api: 'execCommand', inClickTurn });
      return origExec(cmd, ...rest);
    };
  });
}

const resetProbes = (page) => page.evaluate(() => { window.__flashes = []; window.__clipboardCalls = []; });
const probes = (page) => page.evaluate(() => ({ flashes: window.__flashes, calls: window.__clipboardCalls }));

// Reads the OS clipboard the only way that works uniformly across all three engines: focus a real
// textarea and press the paste shortcut. (navigator.clipboard.readText needs a permission that
// Firefox and WebKit don't expose to automation.)
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

// Copy a sentinel using the pattern known to work everywhere (synchronous execCommand inside a
// real click), so a failed round-trip indicts the harness, not the page.
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
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
    };
  }, sentinel);
  await page.click('#__primeBtn');
  await page.waitForTimeout(150);
}

// Pick a deck row whose LoadDeck actually serves a decklist. Not every row qualifies: a local clone
// of the prod DB has ownership rows whose `Games/<id>/` folder was never copied down, and those
// return a PHP warning instead of deck text. Using such a row would fail the checks for a reason
// that has nothing to do with the clipboard, so probe first and fail loud if none work.
async function pickHealthyDeckRow(page) {
  const rows = await page.$$eval('.deck-more-btn', (btns) =>
    btns.map((b, i) => {
      const m = /showDeckDropdown\(\s*this\s*,\s*"[^"]*"\s*,\s*"([^"]+)"/.exec(b.getAttribute('onclick') || '');
      return m ? { index: i, deckID: m[1] } : null;
    }).filter(Boolean));
  if (!rows.length) throw new EnvError('no .deck-more-btn rows on the main menu — does Drixx own any decks?');
  for (const row of rows) {
    const text = await page.evaluate(async (id) => {
      const r = await fetch(`/TCGEngine/APIs/LoadDeck.php?deckID=${id}&format=text`);
      return r.ok ? (await r.text()).slice(0, 40) : '';
    }, row.deckID);
    if (/^\s*Leader\b/.test(text)) return row;
  }
  throw new EnvError(`none of Drixx's ${rows.length} decks serve a decklist locally — copy down a Games/<id>/ folder for one of them`);
}

async function openDeckMenu(page, row) {
  await page.locator('.deck-more-btn').nth(row.index).click();
  await page.waitForSelector('#deckDropdownMenu', { timeout: 5000 });
}

async function clickMenuItem(page, label) {
  const item = page.locator('#deckDropdownMenu button', { hasText: label }).first();
  if (!(await item.count())) throw new EnvError(`"${label}" missing from the deck dropdown`);
  await item.click();
}

const issuedInClickTurn = (calls) => calls.length > 0 && calls.every(c => c.inClickTurn);

const TEXT_CASES = [
  // Copy Link is the CONTROL: it always worked (it copies synchronously) and must stay working —
  // copyTextToClipboard was touched to report success, and the menu's unconditional flash removed.
  { label: 'Copy Link', flash: 'Link copied!', looksRight: (t) => /^https?:\/\/\S+$/.test(t.trim()) },
  { label: 'Copy Text', flash: 'Deck text copied!', looksRight: (t) => /^\s*Leader\b/.test(t) && /Main Deck/.test(t) },
  { label: 'Copy JSON', flash: 'Deck JSON copied!', looksRight: (t) => { try { const o = JSON.parse(t); return !!(o && o.leader && o.deck); } catch { return false; } } },
];

harness(async (check) => {
  for (const [engine, type] of Object.entries(ENGINES)) {
    console.log(`\n=== ${engine} ===`);
    const browser = await type.launch();
    const ctx = await browser.newContext(mobileContextOpts(
      engine === 'chromium' ? { permissions: ['clipboard-read', 'clipboard-write'] } : {}));
    const page = await ctx.newPage();
    try {
      await login(page);
      const resp = await page.goto(`${BASE}/SharedUI/Sites/SWUDeck/MainMenu.php`, { waitUntil: 'domcontentloaded' });
      if (resp && resp.status() >= 400) throw new EnvError(`MainMenu HTTP ${resp.status()}`);
      await page.waitForSelector('.deck-more-btn', { timeout: 15000 }).catch(() => {
        throw new EnvError('main menu never rendered a deck row');
      });
      const row = await pickHealthyDeckRow(page);
      console.log(`   (using deck ${row.deckID})`);
      await instrument(page);

      for (const c of TEXT_CASES) {
        const sentinel = `SENTINEL-${engine}-${c.label.replace(/\W/g, '')}`;
        await primeClipboard(page, sentinel);
        const primed = await pasteBack(page);
        if (primed !== sentinel) {
          throw new EnvError(`clipboard paste round-trip failed in ${engine} (got ${JSON.stringify(primed)}) — harness cannot read the clipboard here`);
        }

        await resetProbes(page);
        await openDeckMenu(page, row);
        await clickMenuItem(page, c.label);
        await page.waitForTimeout(1200);          // outlive the deck fetch + the clipboard write

        const got = await pasteBack(page);
        const { flashes, calls } = await probes(page);
        const changed = got !== sentinel;

        check(`${c.label} reaches the clipboard`, changed && c.looksRight(got),
          changed ? `got ${JSON.stringify(got.slice(0, 60))}` : 'clipboard still holds the sentinel — nothing was copied');
        check(`${c.label} issues its clipboard call inside the click turn`, issuedInClickTurn(calls),
          JSON.stringify(calls));
        // The old mobile menu flashed "Text copied!" unconditionally, so a silent failure looked
        // like a success. Exactly one flash, and only on the real outcome.
        check(`${c.label} flashes only the true outcome`, flashes.length === 1 && flashes[0] === c.flash,
          JSON.stringify(flashes));
      }

      // --- Copy Image (Generate Image -> modal -> Copy Image) ---------------------------------
      await openDeckMenu(page, row);
      await clickMenuItem(page, 'Generate Image');
      try {
        await page.waitForSelector('#deckImageModalOverlay', { timeout: 90000 });
      } catch {
        throw new EnvError('deck image never rendered — CreateImage.php slow or failing (needs vendor/ + card art)');
      }
      await resetProbes(page);
      await page.locator('#deckImageModalOverlay button', { hasText: 'Copy Image' }).first().click();
      await page.waitForTimeout(2500);            // outlive the jpeg -> png canvas conversion
      const img = await probes(page);
      check('Copy Image issues its clipboard call inside the click turn', issuedInClickTurn(img.calls),
        JSON.stringify(img.calls));
      check('Copy Image reports success', img.flashes.length === 1 && img.flashes[0] === 'Deck image copied!',
        JSON.stringify(img.flashes));
    } finally {
      await browser.close();
    }
  }
});
