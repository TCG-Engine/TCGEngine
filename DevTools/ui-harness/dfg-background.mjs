import { chromium, firefox, webkit } from 'playwright';
import assert from 'node:assert/strict';

const url = process.env.DFG_URL || 'http://localhost/TCGEngine/DFGSim/';
let completed = 0;
for (const [name, engine] of Object.entries({chromium, firefox, webkit})) {
  if(process.env.DFG_BROWSERS && !process.env.DFG_BROWSERS.split(',').includes(name)) continue;
  let browser;
  try { browser = await engine.launch({headless: true}); }
  catch (error) { console.log(`${name}: unavailable: ${error.message.split('\n')[0]}`); continue; }
  try {
    const warm = await browser.newPage({reducedMotion:'no-preference'});
    await warm.goto(url);
    await warm.waitForFunction(() => {
      const video=document.getElementById('arenaBackdrop');
      const source=DFGAssetPreloader.playbackSource(video.dataset.src);
      source?.release();
      return !!source;
    }, null, {timeout:30000});
    assert.equal(await warm.locator('#home').isVisible(),true);
    assert.equal(await warm.locator('#arenaBackdrop').getAttribute('src'),null,'Menu warms video without playing it');
    await warm.locator('#homePlay').click();
    await warm.locator('#setupForm [type=submit]').click();
    await warm.waitForFunction(()=>document.getElementById('arenaBackdrop').readyState>=2);
    const warmVideo=await warm.locator('#arenaBackdrop').evaluate(video=>({src:video.src,original:new URL(video.dataset.src,location.href).href}));
    assert.ok(warmVideo.src.startsWith('blob:') || warmVideo.src===warmVideo.original,'Use preloaded video or the WebKit-compatible network fallback');
    await warm.close();
    for (const width of [1600, 390]) {
      const page = await browser.newPage({viewport: {width, height: 900}, reducedMotion: 'reduce'});
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(url);
      assert.equal(await page.locator('#arenaBackdrop').getAttribute('src'), null, 'no background download on menu/reduced motion');
      await page.locator('#homePlay').click();
      await page.locator('#setupForm [type=submit]').click();
      await page.locator('#cards .card-tile').first().waitFor();
      assert.equal(await page.locator('#arenaBackdrop').getAttribute('src'), null, 'reduced motion keeps a still courtyard');
      await page.emulateMedia({reducedMotion: 'no-preference'});
      await page.waitForFunction(() => {
        const video = document.getElementById('arenaBackdrop');
        return video.readyState >= 2 && !video.paused && video.classList.contains('is-playing');
      });
      const info = await page.locator('#arenaBackdrop').evaluate(video => ({
        duration: video.duration, muted: video.muted, loop: video.loop, inline: video.playsInline,
        videoZ: getComputedStyle(video).zIndex,
        fighterZ: getComputedStyle(document.querySelector('.arena-fighter')).zIndex,
        width: video.getBoundingClientRect().width,
        stageWidth: document.querySelector('.arena-art').getBoundingClientRect().width,
        overflow: document.documentElement.scrollWidth > innerWidth,
      }));
      assert.ok(info.duration >= 29 && info.duration <= 31);
      assert.ok(info.muted && info.loop && info.inline);
      assert.ok(Number(info.videoZ) < Number(info.fighterZ));
      assert.equal(info.width, info.stageWidth);
      assert.equal(info.overflow, false);
      await page.locator('#matchMenu summary').click();
      await page.locator('#goHome').click();
      await page.waitForFunction(() => document.getElementById('arenaBackdrop').paused);
      await page.locator('#resumeMatch').click();
      await page.waitForFunction(() => !document.getElementById('arenaBackdrop').paused);
      await page.emulateMedia({reducedMotion: 'reduce'});
      await page.waitForFunction(() => {
        const video = document.getElementById('arenaBackdrop');
        return video.paused && video.hidden;
      });
      assert.deepEqual(errors, []);
      console.log(`${name} ${width}: ambient playback, layer order, menu pause/resume, reduced motion passed`);
      completed++;
      await page.close();
    }
  } finally { await browser.close(); }
}
assert.ok(completed > 0);
