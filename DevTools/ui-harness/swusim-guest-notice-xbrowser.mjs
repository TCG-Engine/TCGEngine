// Cross-browser check for the guest format notice (#guest-format-notice), added 2026-09-20.
//
// The note tells a logged-out visitor that Open lobbies and 1P modes are all they can play. It is
// server-rendered inside `<?php if (!$swuLoggedIn) ?>`, so the ONE thing that can go wrong is the
// discriminator: rendering for everyone, or for nobody. Both halves are asserted here — a harness that
// only checked "the guest sees it" would pass on a note that is permanently visible.
//
// Usage:
//   node swusim-guest-notice-xbrowser.mjs
//   node swusim-guest-notice-xbrowser.mjs http://localhost:3400/TCGEngine/
//
// ⚠ WEBKIT: on Playwright >= 1.62 newPage() hangs (frozen webkit_mac14_special build vs a newer
// driver). The pinned local install is 1.61.1, so webkit launches directly. If you upgrade past 1.62,
// route it through a playwright@1.52.0 side-install via PW152, as waiting-room-xbrowser.mjs documents.
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const CRED = { user: 'claudebot1', pass: 'pass' };
const ENGINES = { chromium, firefox, webkit };

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra) => {
  if (!cond) allOk = false;
  results.push([engine, name, !!cond, extra]);
};

const noticeState = (page) => page.evaluate(() => {
  const el = document.querySelector('#guest-format-notice');
  if (!el) return { present: false };
  const a = el.querySelector('a');
  const cs = getComputedStyle(el);
  const r = el.getBoundingClientRect();
  return {
    present: true,
    visible: cs.display !== 'none' && cs.visibility !== 'hidden' && r.height > 0,
    text: el.textContent.replace(/\s+/g, ' ').trim(),
    href: a ? a.getAttribute('href') : null,
    linkText: a ? a.textContent.trim() : null,
    // Does the link actually read as a link against the body text?
    linkDiffers: a ? getComputedStyle(a).color !== cs.color : false,
    right: Math.round(r.right),
  };
});

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', CRED.user);
  await page.fill('input[name="password"]', CRED.pass);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
}

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();

    // ── LOGGED OUT: a fresh context, so no session cookie exists at all. ──
    const guestCtx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
    const guest = await guestCtx.newPage();
    await guest.goto(MENU, { waitUntil: 'load' });
    await guest.waitForTimeout(500);
    const g = await noticeState(guest);
    ok(engine, 'guest sees the notice', g.present && g.visible, JSON.stringify(g));
    ok(engine, 'the notice names Open and 1P', /Open lobbies and 1P modes/.test(g.text || ''), g.text);
    ok(engine, 'the login link points at LoginPage.php', (g.href || '').endsWith('/SharedUI/LoginPage.php'), g.href);
    ok(engine, 'the link is visually distinct from the body text', g.linkDiffers === true);

    // Phone width: the note must wrap inside the card, not overflow it.
    await guest.setViewportSize({ width: 420, height: 900 });
    await guest.waitForTimeout(300);
    const narrow = await noticeState(guest);
    const docW = await guest.evaluate(() => document.documentElement.clientWidth);
    ok(engine, 'the notice stays inside the viewport at 420px', narrow.right <= docW, `right=${narrow.right} doc=${docW}`);

    // ── GUEST ARRIVING ON AN INVITE: the note must NOT appear. ──
    // An invite exempts the joiner from JoinQueue's login gate entirely, so a guest really can play the
    // host's format whatever it is — telling them "Open lobbies only" would be wrong. Both param
    // spellings that SharedUI/js/private-invite.js accepts are checked; the code need not resolve to a
    // real lobby, because the note is gated on ARRIVING with one, not on the lookup succeeding.
    await guest.setViewportSize({ width: 1400, height: 1000 });
    for (const param of ['privateInvite', 'invite']) {
      await guest.goto(MENU + '?' + param + '=deadbeefdeadbeefdeadbeef', { waitUntil: 'load' });
      await guest.waitForTimeout(500);
      const inv = await noticeState(guest);
      ok(engine, `guest on ?${param}= does NOT get the notice`, inv.present === false, JSON.stringify(inv));
    }
    await guestCtx.close();

    // ── LOGGED IN: the note must be ABSENT FROM THE DOM, not merely hidden. ──
    const userCtx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
    const user = await userCtx.newPage();
    await login(user);
    await user.goto(MENU, { waitUntil: 'load' });
    await user.waitForTimeout(500);
    const u = await noticeState(user);
    ok(engine, 'a logged-in player does NOT get the notice', u.present === false, JSON.stringify(u));
    await userCtx.close();
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}

for (const [engine, name, pass, extra] of results) {
  console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
}
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
