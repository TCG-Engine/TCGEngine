// Shared harness helpers for the cross-browser UI regression suites.
//
// De-brittles the suites: one place for login + navigation + the (previously copy-pasted, brittle)
// selectors and fixed sleeps. Two rules it enforces everywhere:
//   • CONDITION-BASED waits, never fixed `waitForTimeout(3500)` — navigation waits until the board
//     has actually rendered a card, so a slow load doesn't produce a false FAIL.
//   • FAIL-LOUD on a not-ready environment — a missing test deck / down stack / failed login throws
//     EnvError, which `harness()` reports as "ENVIRONMENT NOT READY" and exits 2. That is DISTINCT
//     from a regression (exit 1), so a red result is trustworthy.
//
// Suites keep their own engine loop + assertions; they just build on these helpers.
import { chromium, firefox, webkit } from 'playwright';

export const ENGINES = { chromium, firefox, webkit };
export const BASE = (process.env.BASE || 'http://localhost:3100/TCGEngine').replace(/\/$/, '');

// Thrown for environment problems (stack down, deck folder missing, login failed, board never
// rendered). NOT a regression — harness() maps it to exit code 2 with a clear message.
export class EnvError extends Error {}

export function makeChecker() {
  let failures = 0;
  const check = (label, pass, detail) => {
    if (!pass) failures++;
    console.log(`   ${pass ? 'PASS' : 'FAIL'}  ${label}${detail ? ` — ${detail}` : ''}`);
  };
  return { check, get failures() { return failures; } };
}

// Standard entry point for a suite. Runs `fn(check)`, then summarises + sets the exit code:
//   0 = all checks passed · 1 = a check FAILED (real regression) · 2 = environment not ready.
export async function harness(fn) {
  const checker = makeChecker();
  try {
    await fn(checker.check);
  } catch (e) {
    if (e instanceof EnvError) {
      console.error(`\n⚠ ENVIRONMENT NOT READY — ${e.message}`);
      console.error('  (not a regression) bring up the stack at ' + BASE + ', the Drixx login, and the test decks, then re-run.');
      process.exit(2);
    }
    throw e;
  }
  const f = checker.failures;
  console.log(`\n${f === 0 ? 'ALL CHECKS PASSED' : f + ' CHECK(S) FAILED'}`);
  process.exit(f === 0 ? 0 : 1);
}

export const mobileContextOpts = (over = {}) =>
  ({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, hasTouch: true, isMobile: true, ...over });
export const desktopContextOpts = (over = {}) =>
  ({ viewport: { width: 1600, height: 950 }, deviceScaleFactor: 1, ...over });

export async function login(page, user = 'Drixx', pass = 'pass') {
  const resp = await page.goto(`${BASE}/SharedUI/LoginPage.php`);
  if (resp && resp.status() >= 400) throw new EnvError(`login page HTTP ${resp.status()} — is the stack up at ${BASE}?`);
  try {
    await page.fill('input[name="userID"]', user);
    await page.fill('input[name="password"]', pass);
    await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
  } catch (e) {
    throw new EnvError(`login as ${user} failed (${e.message.split('\n')[0]})`);
  }
}

// Card-ish elements the suites anchor to. A rendered board always has these; used as the
// "board is ready" signal AND as the shared card locator.
export const CARD_SELECTOR = "a[onmouseover*='ShowCardDetail'], img[alt='Card']";
// The hover-preview anchor specifically (carries the inline onmouseover='ShowCardDetail').
export const HOVER_ANCHOR = "a[onmouseover*='ShowCardDetail']";

// Navigate to a deck's board and WAIT (condition-based) until a card has actually rendered.
// Replaces the old fixed `waitForTimeout(3500)`. Throws EnvError if the deck never renders
// (missing Games/<id> folder, stack down, auth failed) — so that is not mistaken for a regression.
// folderPath defaults to SWUDeck but is a param so this same helper serves other roots later.
// `hoverReady`: also block until the card-detail PREVIEW infra is live. A card having size is not
// enough — the freshly-loaded board's initial render churn invalidates the preview's pending-token,
// so the first hover silently no-ops (the old suites papered over this with a flat 3500ms sleep, which
// still flaked under load). Instead we PROBE with a real hover until the preview actually renders, then
// clear it. Self-verifying + condition-based: it waits for exactly the capability preview suites need.
export async function openBoard(page, { game, folderPath = 'SWUDeck', mobile = false, hoverReady = false, timeout = 20000 }) {
  const url = `${BASE}/NextTurn.php?gameName=${game}&playerID=1&folderPath=${folderPath}${mobile ? '&swuLayout=mobile' : ''}`;
  const resp = await page.goto(url, { waitUntil: 'domcontentloaded' });
  if (resp && resp.status() >= 400) throw new EnvError(`${folderPath} deck ${game}: HTTP ${resp.status()}`);
  try {
    await page.waitForFunction(
      (sel) => [...document.querySelectorAll(sel)].some(el => el.getBoundingClientRect().width > 20),
      CARD_SELECTOR, { timeout });
  } catch (e) {
    throw new EnvError(`${folderPath} deck ${game}: no card rendered within ${timeout}ms (does Games/${game}/ exist? stack up? login ok?)`);
  }
  if (hoverReady) {
    const deadline = Date.now() + timeout;
    let ready = false;
    while (Date.now() < deadline && !ready) {
      ready = await page.evaluate(async (sel) => {
        const a = [...document.querySelectorAll(sel)].find(el => el.getBoundingClientRect().width > 20);
        if (!a) return false;
        const r = a.getBoundingClientRect();
        a.dispatchEvent(new MouseEvent('mouseover', { bubbles: true, clientX: r.x + 5, clientY: r.y + 5 }));
        await new Promise(res => setTimeout(res, 600));   // outlive the ~240ms hover delay + image load
        const el = document.getElementById('cardDetail');
        const ok = !!el && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().width > 0;
        if (typeof window.HideCardDetail === 'function') window.HideCardDetail();   // clean the probe up
        return ok;
      }, HOVER_ANCHOR);
      if (!ready) await page.waitForTimeout(300);
    }
    if (!ready) throw new EnvError(`${folderPath} deck ${game}: hover-preview infra never became ready within ${timeout}ms`);
  }
}
