// Cross-browser check for guest access: the guest notice (#guest-format-notice, added 2026-09-20) and the in-game
// chat box.
//
// Since 2026-09-21 (owner) a guest plays every format and loses only chat. The note says so — "log in to use in-game
// chat" — and the game page swaps the message box for a "Log in to chat" note (#chatGuestNote). Both are
// server-rendered on the logged-in state, so the ONE thing that can go wrong is the discriminator: rendering for
// everyone, or for nobody. Both halves are asserted here — a harness that only checked "the guest sees it" would
// pass on a note that is permanently visible.
//
// Usage:
//   node swusim-guest-notice-xbrowser.mjs
//   node swusim-guest-notice-xbrowser.mjs http://localhost:3400/TCGEngine/
//
// ⚠ WEBKIT: on Playwright >= 1.62 newPage() hangs (frozen webkit_mac14_special build vs a newer
// driver). The pinned local install is 1.61.1, so webkit launches directly. If you upgrade past 1.62,
// route it through a playwright@1.52.0 side-install via PW152, as waiting-room-xbrowser.mjs documents.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const CRED = { user: 'claudebot1', pass: 'pass' };
const DECK = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/premier_deck_a.txt', import.meta.url), 'utf8');
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
    ok(engine, 'the notice says an account is for chat', /log in to use in-game chat/i.test(g.text || ''), g.text);
    ok(engine, 'the notice no longer limits what a guest can play', !/Open lobbies|1P modes only/i.test(g.text || ''), g.text);
    ok(engine, 'the login link points at LoginPage.php', (g.href || '').endsWith('/SharedUI/LoginPage.php'), g.href);
    ok(engine, 'the link is visually distinct from the body text', g.linkDiffers === true);

    // Phone width: the note must wrap inside the card, not overflow it.
    await guest.setViewportSize({ width: 420, height: 900 });
    await guest.waitForTimeout(300);
    const narrow = await noticeState(guest);
    const docW = await guest.evaluate(() => document.documentElement.clientWidth);
    ok(engine, 'the notice stays inside the viewport at 420px', narrow.right <= docW, `right=${narrow.right} doc=${docW}`);

    // ── GUEST ARRIVING ON AN INVITE: the note DOES appear now. ──
    // It used to be suppressed there, because it said "Open lobbies only" and an invite let a guest play any
    // format. It now talks about chat, which an invite does not unlock, so it is true on this page too.
    await guest.setViewportSize({ width: 1400, height: 1000 });
    await guest.goto(MENU + '?privateInvite=deadbeefdeadbeefdeadbeef', { waitUntil: 'load' });
    await guest.waitForTimeout(500);
    const inv = await noticeState(guest);
    ok(engine, 'a guest on an invite link also gets the notice', inv.present && inv.visible, JSON.stringify(inv));

    // ── IN GAME: a guest reads chat but has no message box. ──
    const chatState = (page) => page.evaluate(() => {
      const note = document.querySelector('#chatGuestNote');
      const r = note ? note.getBoundingClientRect() : null;
      return {
        input: !!document.querySelector('#chatText'),
        note: !!note && r.height > 0 && getComputedStyle(note).display !== 'none',
        text: note ? note.textContent.replace(/\s+/g, ' ').trim() : null,
        toggle: !!document.querySelector('#chatToggleBtn'),
      };
    });
    const startGoldfish = async (page) => {
      await page.goto(MENU, { waitUntil: 'load' });
      await page.selectOption('#swu-gametype-select', 'solo');
      await page.selectOption('#swu-second-select', 'goldfish');
      await page.evaluate(() => switchDeckTab('text'));
      await page.fill('#deck-text', DECK);
      await page.click('#start-solo-btn');
      return page.waitForURL(/NextTurn\.php/, { timeout: 30000 }).then(() => true).catch(() => false);
    };
    ok(engine, 'a guest can start a game', await startGoldfish(guest), guest.url());
    await guest.waitForTimeout(1500);
    const gc = await chatState(guest);
    ok(engine, 'in game, a guest has no chat message box', gc.input === false, JSON.stringify(gc));
    ok(engine, 'in game, a guest sees "Log in to chat"', gc.note && gc.text === 'Log in to chat', JSON.stringify(gc));
    ok(engine, 'in game, a guest can still open the chat log', gc.toggle, JSON.stringify(gc));
    await guest.screenshot({ path: `/tmp/guest-chat-${engine}.png` });
    await guestCtx.close();

    // ── LOGGED IN: the note must be ABSENT FROM THE DOM, not merely hidden. ──
    const userCtx = await browser.newContext({ viewport: { width: 1400, height: 1000 } });
    const user = await userCtx.newPage();
    await login(user);
    await user.goto(MENU, { waitUntil: 'load' });
    await user.waitForTimeout(500);
    const u = await noticeState(user);
    ok(engine, 'a logged-in player does NOT get the notice', u.present === false, JSON.stringify(u));
    ok(engine, 'a logged-in player can start a game', await startGoldfish(user), user.url());
    await user.waitForTimeout(1500);
    const uc = await chatState(user);
    ok(engine, 'in game, a logged-in player has the message box and no guest note', uc.input === true && uc.note === false, JSON.stringify(uc));
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
