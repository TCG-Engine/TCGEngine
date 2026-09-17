// UI-only fixture: real FaBSim script/styles/schema with settings and transport adapters.
// Server auto-pass behavior is covered by DevTools/FaB/shortcuts_test.php.
import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import { fileURLToPath } from 'node:url';
import { chromium, firefox, webkit } from 'playwright';

const root = fileURLToPath(new URL('../../', import.meta.url));
const read = name => fs.readFile(path.join(root, name), 'utf8');
const schema = await read('Schemas/FaBSim/GameSchema.txt');
const registry = JSON.parse(schema.match(/^Module: ShortcutWindows=(.+)$/m)[1]);
const script = await read('FaBSim/Custom/GameUI.js');
const css = (await Promise.all([
  'SharedUI/css/button.css', 'FaBSim/Custom/Interaction.css',
  'FaBSim/Custom/Shortcuts.css', 'FaBSim/Custom/MultiplayerTable.css',
].map(read))).join('\n');
const layout = await read('FaBSim/Custom/GameLayout.php');
const multiplayer = await read('FaBSim/Custom/MultiplayerLayout.php');
const multiplayerCss = [...multiplayer.matchAll(/<style>([\s\S]*?)<\/style>/g)].map(match => match[1].includes('<?php') ? '' : match[1]).join('\n');
const layoutCss = [...layout.matchAll(/<style>([\s\S]*?)<\/style>/g)].map(match => match[1]).join('\n');
const out = process.argv[2] || path.join(os.tmpdir(), 'fab-shortcuts');
await fs.mkdir(out, { recursive: true });
const fixture = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<style>${multiplayerCss}\n${css}\n${layoutCss}</style></head><body>
<div id="fab-duel-layout"><div class="fab-overlay-toolbar"><button class="fab-overlay-button">Combat Chain</button><button class="fab-overlay-button" id="fixture-layers">Layers</button></div></div>
<main id="fab-upf-board"><div class="fab-upf-bar"><p class="fab-upf-status">Your priority</p><nav class="fab-upf-tools"><button>Stack</button><button>Combat chain</button></nav></div></main>
<script>
window.GetModuleConfig = () => JSON.stringify(${JSON.stringify(registry)});
window.TCGSettings = {
  registerSchema() {},
  get(key, options) { const value = localStorage.getItem(key); return value === null ? options.defaultValue : JSON.parse(value); },
  set(key, value) { localStorage.setItem(key, JSON.stringify(value)); }
};
window.submissions = [];
window.SubmitInput = (mode, data) => { if (String(mode) === '10015') submissions.push(JSON.parse(new URLSearchParams(data).get('inputText'))); };
</script><script>${script}</script></body></html>`;

const requestedEngines = (process.argv[3] || 'chromium,firefox,webkit').split(',');
for (const [name, engine] of Object.entries({ chromium, firefox, webkit }).filter(([name]) => requestedEngines.includes(name))) {
  const browser = await engine.launch();
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await context.route('http://fab-shortcuts.test/**', route => route.fulfill({ contentType: 'text/html', body: fixture }));
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://fab-shortcuts.test/');
    const toggle = page.locator('#fab-shortcut-toggle');
    const panel = page.locator('#fab-shortcut-panel');
    const hold = page.locator('#fab-shortcut-hold');
    assert.equal(await panel.isVisible(), false);
    assert.equal(await hold.isVisible(), true);
    assert.equal(await hold.evaluate(el => el.closest('.fab-overlay-toolbar') !== null && !el.closest('#fab-shortcut-panel')), true);
    await hold.check(); assert.equal(await panel.isVisible(), false); await hold.uncheck();
    await toggle.click();
    assert.equal(await toggle.getAttribute('aria-expanded'), 'true');
    assert.equal(await page.locator('.fab-shortcut-close').evaluate(el => el === document.activeElement), true);
    assert.equal(await page.locator('[data-id]').count(), 11);
    await page.locator('[data-id="BLOCK"]').check();
    await page.locator('[data-id="DAMAGE_PRIORITY"]').uncheck();
    const saved = await page.evaluate(() => submissions.at(-1).windows);
    await hold.focus(); await page.keyboard.press('Space');
    assert.equal(await hold.isChecked(), true);
    assert.equal(await page.locator('[data-id]:disabled').count(), 11);
    assert.equal(await page.locator('#fab-shortcut-toggle-label').textContent(), 'Shortcuts');
    assert.deepEqual(await page.evaluate(() => submissions.at(-1).windows), saved);
    assert.equal(await page.evaluate(() => submissions.at(-1).holdPriority), true);
    await page.reload(); await toggle.click();
    assert.equal(await hold.isChecked(), true);
    assert.deepEqual(await page.evaluate(() => submissions.at(-1).windows), saved);
    const auto = page.locator('#fab-shortcut-choices input');
    assert.equal(await auto.isEnabled(), true);
    await auto.check();
    assert.equal(await page.evaluate(() => JSON.parse(localStorage.getItem('AutoChooseSingleOption'))), true);
    await page.keyboard.press('Escape');
    assert.equal(await panel.isVisible(), false);
    assert.equal(await toggle.evaluate(el => el === document.activeElement), true);
    await toggle.click(); await hold.uncheck();
    assert.deepEqual(await page.evaluate(() => submissions.at(-1).windows), saved);
    assert.equal(await page.locator('[data-id]:disabled').count(), 0);
    await page.mouse.click(10, 10);
    assert.equal(await panel.isVisible(), false);

    for (const [view, width, height] of [['desktop', 1440, 1000], ['phone', 390, 844], ['landscape', 844, 390]]) {
      await page.setViewportSize({ width, height });
      await page.evaluate(() => document.body.classList.toggle('fab-upf-active', innerWidth < 900));
      if (!(await panel.isVisible())) await toggle.click();
      assert.equal(await toggle.evaluate(el => el.closest(document.body.classList.contains('fab-upf-active') ? '.fab-upf-tools' : '.fab-overlay-toolbar') !== null), true);
      const bounds = await panel.boundingBox();
      assert.ok(bounds.x >= 0 && bounds.y >= 0 && bounds.x + bounds.width <= width && bounds.y + bounds.height <= height, `${name}/${view}: panel overflow`);
      await auto.scrollIntoViewIfNeeded();
      const autoBounds = await auto.boundingBox();
      assert.ok(autoBounds.y >= bounds.y && autoBounds.y + autoBounds.height <= bounds.y + bounds.height, `${name}/${view}: final control inaccessible`);
      await page.locator('.fab-shortcut-body').evaluate(el => { el.scrollTop = 0; });
      await page.screenshot({ path: path.join(out, `${name}-${view}.png`) });
      await hold.check();
      await page.screenshot({ path: path.join(out, `${name}-${view}-held.png`) });
      await hold.uncheck();
    }
    assert.deepEqual(errors, []);
    console.log(`${name}: saved selections, master hold, reload, keyboard, dismiss, and three viewport layouts passed`);
    await context.close();
    const touch = await browser.newContext({ viewport: { width: 320, height: 568 }, hasTouch: true });
    await touch.route('http://fab-shortcuts.test/**', route => route.fulfill({ contentType: 'text/html', body: fixture }));
    const phone = await touch.newPage();
    await phone.goto('http://fab-shortcuts.test/');
    await phone.locator('#fab-shortcut-toggle').tap();
    await phone.locator('.fab-shortcut-master').tap();
    assert.equal(await phone.locator('#fab-shortcut-hold').isChecked(), true);
    const phoneBounds = await phone.locator('#fab-shortcut-panel').boundingBox();
    assert.ok(phoneBounds.x >= 0 && phoneBounds.y >= 0 && phoneBounds.x + phoneBounds.width <= 320);
    const lastControl = phone.locator('#fab-shortcut-choices input');
    await lastControl.scrollIntoViewIfNeeded(); await lastControl.tap();
    assert.equal(await lastControl.isChecked(), true);
    await phone.locator('.fab-shortcut-body').evaluate(el => { el.scrollTop = 0; });
    await phone.screenshot({ path: path.join(out, `${name}-small-touch.png`) });
    await touch.close();
    console.log(`${name}: small-screen touch controls and scrolling passed`);
  } finally { await browser.close(); }
}
console.log(`Screenshots: ${out}`);
