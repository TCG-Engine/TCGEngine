// node DevTools/ui-harness/sim-seat-handoff-xbrowser.mjs
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import assert from 'node:assert/strict';

const source = readFileSync(new URL('../../SharedUI/Render/WaitingRoom.php', import.meta.url), 'utf8');
const start = source.indexOf("        var seat = spectating ? 'S'");
const end = source.indexOf('        return;', start);
assert.ok(start > 0 && end > start);
const handoff = source.slice(start, end);
for (const [name, engine] of [['Chromium', chromium], ['Firefox', firefox], ['WebKit', webkit]]) {
  if (process.env.BROWSERS && !process.env.BROWSERS.toLowerCase().split(',').includes(name.toLowerCase())) continue;
  const browser = await engine.launch();
  try {
    const context = await browser.newContext();
    await context.addCookies([{ name: 'lastAuthKey', value: 'stale-parent-key', domain: '.talishar.net', path: '/' }]);
    const page = await context.newPage();
    await page.route('**/*', route => route.fulfill({ contentType: 'text/html', body: '<html></html>' }));
    const url = 'https://upf.talishar.net/TCGEngine/SharedUI/Sites/FaBSim/WaitingRoom.php';
    for (const seat of [1, 2, 3, 4, 'S']) {
      await context.clearCookies({ name: 'tcgengine_FaBSim_lastAuthKey' });
      await page.goto(url);
      await page.evaluate(({ handoff, seat }) => {
        const run = new Function('r', 'spectating', 'myPlayerID', 'loadKey', 'lobbyID', 'appBase', 'ROOT', handoff);
        run({ playerID: seat, gameName: '164' }, seat === 'S', seat,
          () => 'fresh-seat-key', 'room', () => '/TCGEngine/', 'FaBSim');
      }, { handoff, seat });
      await page.waitForURL('**/NextTurn.php?**');
      assert.equal(new URL(page.url()).searchParams.get('playerID'), String(seat));
      assert.equal(new URL(page.url()).searchParams.has('authKey'), false);
      const scoped = (await context.cookies()).find(c => c.name === 'tcgengine_FaBSim_lastAuthKey');
      if (seat === 'S') assert.equal(scoped, undefined);
      else {
        assert.equal(scoped?.value, 'fresh-seat-key');
        assert.equal(scoped?.domain, 'upf.talishar.net');
        assert.equal(scoped?.path, '/TCGEngine/');
      }
    }
    console.log(`PASS ${name}: all four seat handoffs and spectator with parent-domain cookie present`);
  } finally { await browser.close(); }
}
