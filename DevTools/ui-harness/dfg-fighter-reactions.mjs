import { chromium, firefox, webkit } from 'playwright';
import assert from 'node:assert/strict';

const url = process.env.DFG_URL || 'http://localhost/TCGEngine/DFGSim/';
let completed = 0;
for (const [name, engine] of Object.entries({ chromium, firefox, webkit })) {
  if(process.env.DFG_BROWSERS && !process.env.DFG_BROWSERS.split(',').includes(name)) continue;
  let browser;
  try { browser = await engine.launch({ headless: true }); }
  catch (error) { console.log(`${name}: unavailable: ${error.message.split('\n')[0]}`); continue; }
  try {
    const page = await browser.newPage({ viewport: { width: 1366, height: 900 }, reducedMotion: 'no-preference' });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const downloads=[];
    page.on('request', request=> { if(/fighter-(blue|red)-hit-/.test(request.url())) downloads.push(request.url()); });
    await page.goto(url);
    await page.waitForFunction(() => {
      const clips=JSON.parse(document.getElementById('fighterReactionAssets').textContent);
      return Object.values(clips).flatMap(set=>Object.values(set)).flatMap(clip=>[clip.src,clip.finalSrc].filter(Boolean)).every(url=> {
        const source=DFGAssetPreloader.playbackSource(url);
        source?.release();
        return !!source;
      });
    }, null, {timeout:30000});
    assert.equal(await page.locator('#home').isVisible(),true,'All reactions warm before starting a fight');
    const warmedDownloads=downloads.length;
    await page.locator('#homePlay').click();
    await page.locator('#setupForm [type=submit]').click();
    await page.locator('#cards .card-tile').first().waitFor();
    for (const [damage, kind] of [[0,'block'],[1,'light'],[2,'light'],[3,'medium'],[4,'medium'],[5,'heavy'],[12,'heavy']]) {
      await page.evaluate(damage => { DFGFighterReactions.play(0, damage); DFGFighterReactions.play(1, damage); }, damage);
      await page.waitForFunction(kind => [...document.querySelectorAll('.arena-fighter')].every(i => i.dataset.reaction === kind && i.complete && i.naturalWidth > 0), kind);
      assert.equal(await page.locator(`.arena-fighter[data-reaction=${kind}]`).count(), 2);
      await page.waitForFunction(() => !document.querySelector('.arena-fighter[data-reaction]'));
    }
    await page.evaluate(() => DFGFighterReactions.syncOutcome(1,{animate:true}));
    await page.waitForFunction(() => document.querySelector('.arena-fighter-player').dataset.reactionState==='held');
    await page.evaluate(() => DFGFighterReactions.reset());
    assert.equal(downloads.length,warmedDownloads,'Repeated hits and knockout reuse downloaded animation data');
    assert.ok(downloads.every(src=>!src.includes('play=')),'Warmed animations never use cache-busting requests');
    await page.evaluate(() => { DFGFighterReactions.play(0, 5); DFGFighterReactions.play(0, 1); });
    await page.waitForFunction(() => document.querySelector('.arena-fighter-player').dataset.reaction === 'light');
    await page.evaluate(() => DFGFighterReactions.reset());
    assert.equal(await page.locator('.arena-fighter[data-reaction]').count(), 0);
    const received = await page.evaluate(() => {
      const calls = [], original = DFGFighterReactions.play;
      DFGFighterReactions.play = (seat, amount) => calls.push([seat, amount]);
      try {
        const before = structuredClone(state), after = structuredClone(state);
        before.phase = 'reactions'; after.phase = 'switching';
        after.stats = [{damage: 0}, {damage: 4}];
        animateMeterChanges(before, after);
        const combatCalls = calls.splice(0);
        animateMeterChanges(after, after);
        return {combatCalls, pollCalls: calls};
      } finally { DFGFighterReactions.play = original; }
    });
    assert.deepEqual(received.combatCalls, [[0,4],[1,0]], 'received damage uses opposing stats, including blocks');
    assert.deepEqual(received.pollCalls, [], 'polling does not replay blocks');
    await page.evaluate(() => { DFGFighterReactions.play(0, 5); DFGFighterReactions.play(1, 3); });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.waitForFunction(() => !document.querySelector('.arena-fighter[data-reaction]'));
    await page.evaluate(() => { DFGFighterReactions.play(0, 0); DFGFighterReactions.play(1, 5); });
    assert.equal(await page.locator('.arena-fighter[data-reaction]').count(), 0);
    await page.waitForFunction(() => document.querySelector('.arena-fighter-player').currentSrc.includes('fighter-blue-transparent.webp'));
    assert.deepEqual(errors, []);
    console.log(`${name}: all damage thresholds, simultaneous playback, idle recovery, interruption, combat hook, reduced motion passed`);
    completed++;
  } finally { await browser.close(); }
}
assert.ok(completed > 0, 'At least one browser must run');
