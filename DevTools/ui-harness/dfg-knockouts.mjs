import { chromium, firefox, webkit } from 'playwright';
import assert from 'node:assert/strict';

const url = process.env.DFG_URL || 'http://localhost/TCGEngine/DFGSim/';
let completed = 0;
for (const [name, engine] of Object.entries({chromium, firefox, webkit})) {
  let browser;
  try { browser = await engine.launch({headless: true}); }
  catch (error) { console.log(`${name}: unavailable: ${error.message.split('\n')[0]}`); continue; }
  try {
    for (const width of [1600, 390]) {
      const page = await browser.newPage({viewport: {width, height: 900}, reducedMotion: 'no-preference'});
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(url);
      await page.locator('#homePlay').click();
      await page.locator('#setupForm [type=submit]').click();
      await page.locator('#cards .card-tile').first().waitFor();
      for (const winner of [0, 1, 'draw']) {
        await page.evaluate(winner => {
          DFGFighterReactions.reset();
          const before = structuredClone(state), after = structuredClone(state);
          before.phase = 'reactions'; after.phase = 'finished'; after.winner = winner;
          after.players.forEach((player, seat) => { if (winner === 'draw' || winner !== seat) player.life = 0; });
          after.stats = [{damage: winner === 1 ? 0 : 40}, {damage: winner === 0 ? 0 : 40}];
          animateMeterChanges(before, after);
        }, winner);
        const count = winner === 'draw' ? 2 : 1;
        assert.equal(await page.locator('[data-reaction=knockout]').count(), count);
        await page.waitForFunction(count => document.querySelectorAll('[data-reaction=knockout][data-reaction-state=held]').length === count, count);
        assert.equal(await page.locator('.arena-fighter[data-reaction]:not([data-reaction=knockout])').count(), 0);
        const sources = await page.locator('[data-reaction=knockout]').evaluateAll(images => images.map(i => i.getAttribute('src')));
        assert.ok(sources.every(src => src.includes('knockout-final.webp')));
        await page.evaluate(winner => {
          DFGFighterReactions.syncOutcome(winner, {animate: true});
          DFGFighterReactions.play(0, 5); DFGFighterReactions.play(1, 5);
        }, winner);
        assert.equal(await page.locator('[data-reaction=knockout][data-reaction-state=held]').count(), count, 'polls and hits cannot revive the defeated fighter');
        await page.evaluate(() => DFGFighterReactions.reset());
        assert.equal(await page.locator('.arena-fighter[data-reaction]').count(), 0);
        assert.equal(await page.locator('.arena-art picture').count(), 2, 'reset restores both idle picture sources');
      }
      await page.evaluate(() => DFGFighterReactions.syncOutcome('draw', {animate: true}));
      await page.emulateMedia({reducedMotion: 'reduce'});
      await page.waitForFunction(() => document.querySelectorAll('[data-reaction-state=held]').length === 2);
      await page.evaluate(() => DFGFighterReactions.reset());
      // Reloading a finished match skips the fall, even without reduced motion.
      await page.emulateMedia({reducedMotion: 'no-preference'});
      await page.evaluate(() => {
        const finished = structuredClone(state); finished.phase = 'finished'; finished.winner = 1;
        animateMeterChanges(null, finished);
      });
      assert.equal(await page.locator('.arena-fighter-player[data-reaction-state=held]').count(), 1);
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
      assert.deepEqual(errors, []);
      console.log(`${name} ${width}: victory/defeat/double KO, held final pose, no revival, reset, reduced motion, restored match passed`);
      completed++;
      await page.close();
    }
  } finally { await browser.close(); }
}
assert.ok(completed > 0);
