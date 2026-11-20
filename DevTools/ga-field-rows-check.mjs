import {execFileSync} from 'node:child_process';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const {chromium, firefox, webkit} = await import(process.env.PLAYWRIGHT_MODULE || 'playwright');
const css = execFileSync('php', ['GrandArchiveSim/Custom/FieldRows.php'], {encoding:'utf8'}).split('<script>')[0];
const mobile = execFileSync('php', ['GrandArchiveSim/Custom/GameLayoutMobile.php'], {encoding:'utf8'});
const desktop = execFileSync('php', ['GrandArchiveSim/Custom/GameLayout.php'], {encoding:'utf8'}).replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');
for (const [name, engine] of Object.entries({chromium, firefox, webkit})) {
 const browser = await engine.launch();
 try {
  for (const [width, height] of [[390,844], [1280,768], [1280,600], [1600,1400]]) {
   const page = await browser.newPage({viewport:{width,height}});
   await page.setContent(css + (width === 390 ? mobile : desktop));
   await page.evaluate(() => {
    window.typeData = {a:'ALLY',c:'CHAMPION',w:'REGALIA,WEAPON',s:'REGALIA,ITEM'};
    window.GetZoneCard = id => ({CardID:['s','c','a','w'][Number(id.split('-')[1]) % 4]});
    window.clicks = 0;
    window.render = () => {
     for (const id of ['myField','theirField']) {
      const slot = document.getElementById(id+'Slot');
      slot.innerHTML = `<div id="${id}Wrapper"><div id="${id}"></div></div>`;
      for(let i=0;i<60;i++) {
       const card = document.createElement('span'); card.id=id+'-'+i;
       card.innerHTML='<a><img src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'63\' height=\'88\'%3E%3Crect width=\'63\' height=\'88\' fill=\'teal\'/%3E%3C/svg%3E"></a>';
       card.onclick = () => window.clicks++;
       document.getElementById(id).append(card);
      }
     }
    }; render();
   });
   await page.addScriptTag({content:readFileSync('GrandArchiveSim/Custom/FieldRows.js','utf8')});
   await page.waitForTimeout(150);
   assert.deepEqual(await page.locator('#myField h3').allTextContents(), height >= 1300 ? ['Allies · 15','Champion & Weapons · 30','Support · 15'] : ['Units · 30','Support · 30']);
   assert.deepEqual(await page.locator('#theirField h3').allTextContents(), height >= 1300 ? ['Support · 15','Champion & Weapons · 30','Allies · 15'] : ['Support · 30','Units · 30']);
   const bounds = await page.evaluate(() => {
    const mine = document.getElementById('myFieldSlot').getBoundingClientRect();
    const theirs = document.getElementById('theirFieldSlot').getBoundingClientRect();
    return {mine:mine.top, theirs:theirs.bottom};
   });
   assert.ok(bounds.theirs <= bounds.mine, 'player fields must not overlap');
   for (const id of ['myField', 'theirField']) {
    assert.ok(await page.locator('#'+id+'Wrapper').evaluate(el => {
     const slot = el.parentElement.getBoundingClientRect(), rect = el.getBoundingClientRect();
     el.scrollTop = 10000;
     return rect.bottom <= slot.bottom + 1 && rect.top >= slot.top - 1;
    }), 'multi-row field stays inside its slot and scrolls vertically');
   }
   if (width === 1280 && height === 768) {
    assert.ok(await page.locator('#myFieldWrapper').evaluate(el => el.scrollHeight <= el.clientHeight + 1), 'both laptop rows fit without vertical scrolling');
   }
   // With many mocked champions the sticky cards cover allies; click a champion instead.
   await page.locator('#myField-1').click();
   assert.equal(await page.evaluate(()=>clicks),1);
   assert.equal(await page.locator('#myField .ga-field-lane > span').count(),60);
   assert.ok(await page.locator('#myField .ga-field-lane').first().evaluate(el => {el.scrollLeft=10000;return el.scrollLeft>0;}));
   await page.evaluate(()=>render()); await page.waitForTimeout(150);
   assert.equal(await page.locator('#myField .ga-field-row').count(),height >= 1300 ? 3 : 2);
   assert.equal(await page.locator('#myField .ga-field-lane > span').count(),60);
   await page.evaluate(()=>{window.changes=0; new MutationObserver(m=>changes+=m.length).observe(document.getElementById('myField'),{childList:true,subtree:true});});
   await page.waitForTimeout(250); assert.equal(await page.evaluate(()=>changes),0);
   await page.locator('#ga-field-display').evaluate(el => el.open = true);
   // Open mobile game menu directly: layout fixture excludes its application scripts.
   await page.locator('#ga-field-display').evaluate(el => { if (el.parentElement.id === 'gaMobileAdminMenuPanel') el.parentElement.style.display = 'block'; });
   await page.locator('#ga-field-display select').selectOption('3', {force:true});
   await page.waitForTimeout(100);
   assert.equal(await page.locator('#myField .ga-field-row').count(),3);
   await page.locator('#ga-field-display select').selectOption('2', {force:true});
   await page.waitForTimeout(100);
   assert.equal(await page.locator('#myField .ga-field-row').count(),2);
   assert.ok(await page.locator('#myField-1').evaluate(el => getComputedStyle(el).position === 'sticky'));
   await page.locator('#theirField-2').evaluate(el => el.classList.add('combat-attacker-card'));
   await page.waitForTimeout(100);
   assert.ok(await page.locator('#theirField-2').evaluate(el => {
    const r = el.getBoundingClientRect(), lane = el.closest('.ga-field-lane').getBoundingClientRect();
    return r.right <= lane.right + 1 && r.left >= lane.left - 1;
   }), 'active attacker brought into view');
   await page.addScriptTag({content:readFileSync('GrandArchiveSim/Custom/CleanUI.js','utf8')});
   await page.waitForTimeout(100);
   assert.equal(await page.locator('#ga-settings #ga-field-display').count(),1);
   assert.equal(await page.locator('#ga-history').count(),1);
   if (width !== 390) {
    assert.equal(await page.locator('#ga-action-area #myHealthSlot').count(),1);
    assert.equal(await page.evaluate(()=>SelectionMessageSettings.embedTargetId),'ga-action-area');
   }
   await page.locator('#ga-settings').evaluate(el=>el.open=true);
   await page.keyboard.press('Escape');
   assert.equal(await page.locator('#ga-settings').evaluate(el=>el.open),false);
   console.log(name,width,'grouping, mirroring, original clicks, scrolling, refresh, idle passed');
   await page.close();
  }
 } finally {await browser.close();}
}
