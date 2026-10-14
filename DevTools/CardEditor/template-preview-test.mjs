import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { chromium, firefox, webkit } from '../ui-harness/node_modules/playwright/index.mjs';

const template = JSON.parse(await fs.readFile(new URL('./dfg-template.json', import.meta.url), 'utf8'));
const cards = JSON.parse(await fs.readFile(new URL('../../DFGSim/data/cards.json', import.meta.url), 'utf8')).cards;
const renderer = await fs.readFile(new URL('../../CardEditor/UI/PreviewRenderer.js', import.meta.url), 'utf8');
const valuesFor = card => [
  [1, card.name], [2, card.color], [3, card.ability], [4, card.bonus], [5, card.reaction]
].map(([field_id, value_text]) => ({ field_id, value_text })).concat([
  {field_id: 6, value_json: card.switches}, {field_id: 7, value_number: card.attack}, {field_id: 8, value_number: card.block}
]);
const styles = `*{box-sizing:border-box}body{margin:0;padding:28px;background:#e0e5eb;font-family:Arial,sans-serif}h1{font-size:22px;color:#203047}#cards{display:flex;gap:24px;flex-wrap:wrap}.card-preview-canvas{position:relative;overflow:hidden;border-radius:16px;box-shadow:0 8px 20px #0003}.preview-el{position:absolute;overflow:hidden}.image-el img{width:100%;height:100%;display:block}`;
const sampleIds = [1, 30, 12];
const samples = cards.filter(card => sampleIds.includes(card.id)).sort((a,b)=>sampleIds.indexOf(a.id)-sampleIds.indexOf(b.id));
const preview = `<!doctype html><html lang="en"><meta charset="utf-8"><title>Fighting Card template</title><style>${styles}</style><h1>Deterministic Fighting Game · card template draft</h1><div id="cards"></div><script>${renderer}\nconst template=${JSON.stringify(template)};const samples=${JSON.stringify(samples.map(valuesFor))};for(const values of samples){const host=document.createElement('div');document.getElementById('cards').append(host);PreviewRenderer.render(host,template,values,{scale:0.5});}</script></html>`;
await fs.writeFile(new URL('./template-preview.html', import.meta.url), preview);

for (const [name, browserType] of Object.entries({chromium,firefox,webkit})) {
  if (!(process.env.CARD_TEMPLATE_BROWSERS || 'chromium,webkit').split(',').includes(name)) continue;
  const browser = await browserType.launch({headless:true});
  try {
    const page = await browser.newPage({viewport:{width:1250,height:670}});
    await page.setContent(preview);
    assert.equal(await page.locator('[role=img]').count(), samples.reduce((n,c)=>n+c.switches.length,0));
    await page.screenshot({path:new URL(`./template-${name}.png`,import.meta.url).pathname.replace(/^\/([A-Za-z]:)/,'$1')});
    for (const card of cards) {
      const result = await page.evaluate(({template, values}) => {
        const host = document.getElementById('cards');
        PreviewRenderer.render(host,template,values);
        return [...host.querySelectorAll('.text-el')].filter(el=>el.scrollHeight>el.clientHeight+1 || el.scrollWidth>el.clientWidth+1).map(el=>el.textContent);
      }, {template, values:valuesFor(card)});
      assert.deepEqual(result, [], `${name}: ${card.name} text fits its panels`);
      assert.equal(await page.locator('[role=img]').count(),card.switches.length, 'exactly the listed hand colors render');
    }
    await page.evaluate(({template})=>{
      const host=document.getElementById('cards');
      PreviewRenderer.render(host,template,[{field_id:7,value_number:0},{field_id:6,value_json:['Blue','Blue','<script>']},{field_id:11,value_text:'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg"/%3E'}]);
    },{template});
    assert.equal(await page.locator('.text-el[data-element-id="'+template.layout.find(e=>e.field_id===7).id+'"]').innerText(),'0','zero stat is preserved');
    assert.equal(await page.locator('[role=img]').count(),1,'duplicate colors are deduplicated and unknown colors remain text');
    assert.equal(await page.locator('.image-el img').count(),1,'assigned artwork replaces the placeholder');
    console.log(`${name}: all 36 cards fit; switch orbs, zero stats, and artwork pass`);
  } finally {await browser.close();}
}
