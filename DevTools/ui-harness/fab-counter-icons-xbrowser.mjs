// Verify generated schema counters against the actual FaBSim card sizing CSS.
import fs from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import { chromium, webkit } from 'playwright';
const root = fileURLToPath(new URL('../../', import.meta.url));
const read = name => fs.readFile(path.join(root, name), 'utf8');
const generated = (await fs.readdir(path.join(root, 'FaBSim'))).find(name => /^GeneratedUI_.*\.js$/.test(name));
const rules = (await read('FaBSim/' + generated)).match(/const CounterRules = [^\r\n]+/)[0];
const renderer = await read('Core/CounterRendering.js');
const css = await read('FaBSim/Custom/IconTheme.css');
const layout = await read('FaBSim/Custom/GameLayout.php');
const layoutCss = [...layout.matchAll(/<style>([\s\S]*?)<\/style>/g)].map(match => match[1]).join('\n');
const out = process.argv[2] || path.join(os.tmpdir(), 'fab-counter-icons');
await fs.mkdir(out, { recursive: true });
for (const [name, engine] of Object.entries({ chromium, webkit })) {
  const browser = await engine.launch();
  try {
    const page = await browser.newPage({ viewport: { width: 620, height: 340 } });
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    await page.route('http://fab-icons.test/**', async route => {
      const resource = new URL(route.request().url()).pathname.slice(1);
      if (resource) return route.fulfill({ body: await fs.readFile(path.join(root, resource)) });
      return route.fulfill({ contentType: 'text/html', body: `<meta charset="utf-8"><style>${css}\n${layoutCss}
        body{padding:40px;display:flex;gap:50px}.fab-zone{position:relative;inset:auto;width:180px;height:180px;padding:0;--fab-card-size:180px}
        .sample{position:relative;width:180px;height:180px;background:#27363c}
        </style><div class="fab-zone"><div class="sample" id="equipment"></div></div><div class="fab-upf-zone"><div class="sample" id="arena"></div></div>
        <script>${rules}\n${renderer}</script>` });
    });
    await page.goto('http://fab-icons.test/');
    await page.evaluate(() => {
      document.querySelector('#equipment').innerHTML = CreateCountersHTML('Equipment', ['tunic', '2', JSON.stringify({ EnergyCounters: 3, DefenseCounters: 1 })], 'tunic');
      document.querySelector('#arena').innerHTML = CreateCountersHTML('Arena', ['permanent', '2', JSON.stringify({ EnergyCounters: 2 })], 'permanent');
    });
    for (const id of ['equipment', 'arena']) {
      const card = await page.locator('#' + id).boundingBox();
      const counter = await page.locator(`#${id} [data-counter-field="EnergyCounters"]`).boundingBox();
      assert.ok(Math.abs(counter.x + counter.width / 2 - card.x - card.width / 2) < 1);
      assert.ok(Math.abs(counter.y + counter.height / 2 - card.y - card.height / 2) < 1);
    }
    const defense = await page.locator('[data-counter-field="DefenseCounters"]').boundingBox();
    const card = await page.locator('#equipment').boundingBox();
    assert.ok(defense.x > card.x + card.width / 2 && defense.y > card.y + card.height / 2);
    for (const icon of await page.locator('.counter-image-icon').all()) {
      await icon.evaluate(img => img.decode());
      const bounds = await icon.boundingBox(); assert.equal(bounds.width, 28); assert.equal(bounds.height, 28);
    }
    await page.screenshot({ path: path.join(out, `${name}.png`) });
    assert.equal(await page.evaluate(() => CreateCountersHTML('Equipment', ['c', '2', JSON.stringify({ EnergyCounters: 0, DefenseCounters: 0 })], 'zero')), '');
    assert.ok(await page.evaluate(() => CreateCountersHTML('Equipment', ['c', '2', JSON.stringify({ DefenseCounters: -1 })], 'negative').includes('>-1</div>')));
    assert.deepEqual(errors, []);
    console.log(`${name}: icon loading, size, centered energy, bottom-right armor, zero and signed values passed`);
  } finally { await browser.close(); }
}
