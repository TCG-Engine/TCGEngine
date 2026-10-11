// scratch: start a real 3-seat Twin Suns game and screenshot seat 1's board
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
const [,, S, ENG = 'chromium', W = '1728', H = '1000'] = process.argv;
const BASE = 'http://localhost:3400/TCGEngine/';
const DECK = fs.readFileSync('/Users/mt/Documents/GitHub/Karabast-SWU/OTMTCGE/SWUSim/Tests/BotFixtures/twinsuns_deck_a.txt', 'utf8');
const b = await ({ chromium, firefox, webkit })[ENG].launch();
const host = await (await b.newContext({ viewport: { width: +W, height: +H } })).newPage();
await host.goto(BASE + 'SharedUI/Sites/SWUSim/MainMenu.php', { waitUntil: 'load' });
await host.selectOption('#swu-gametype-select', 'twinsuns'); await host.selectOption('#swu-second-select', 'ffa');
await host.evaluate(() => switchDeckTab('text')); await host.fill('#deck-text', DECK);
await Promise.all([host.waitForURL(/WaitingRoom/, { timeout: 30000 }), host.click('#create-private-game-btn')]);
await host.waitForFunction(() => /[0-9a-f]{16,}/i.test((document.getElementById('wr-invite') || {}).textContent || ''), null, { timeout: 15000 });
const invite = await host.evaluate(() => document.getElementById('wr-invite').textContent.match(/[0-9a-f]{16,}/i)[0]);
for (let j = 0; j < 2; j++) {
  const g = await (await b.newContext()).newPage();
  await g.goto(BASE + 'SharedUI/Sites/SWUSim/MainMenu.php?privateInvite=' + invite, { waitUntil: 'load' }); await g.waitForTimeout(900);
  await g.evaluate(() => switchDeckTab('text')); await g.fill('#deck-text', DECK);
  await Promise.all([g.waitForURL(/WaitingRoom/, { timeout: 30000 }).catch(() => {}), g.click('#join-private-invite-btn')]);
}
await host.waitForFunction(() => { const s = document.getElementById('wr-start'); return s && !s.disabled; }, null, { timeout: 20000 });
await Promise.all([host.waitForURL(/NextTurn/, { timeout: 30000 }), host.click('#wr-start')]);
await host.waitForTimeout(4000);
await host.screenshot({ path: `${S}/ts-board-${ENG}-${W}.png` });
fs.writeFileSync(`${S}/ts-url.txt`, host.url());
if (process.env.PROBE) console.log(await host.evaluate(new Function(process.env.PROBE)));
console.log(host.url());
await b.close();
