import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const require = createRequire(new URL('../../ui-harness/package.json',import.meta.url));
const {chromium,firefox,webkit} = require('playwright');
const url = process.env.STUDY_URL || new URL('./index.html',import.meta.url).href;
const screenshots = process.env.STUDY_SCREENSHOTS;
if(screenshots)await mkdir(screenshots,{recursive:true});
let passed=0, skipped=[];
for(const [name,engine] of Object.entries({chromium,firefox,webkit})) {
  if(process.env.STUDY_BROWSERS&&!process.env.STUDY_BROWSERS.split(',').includes(name))continue;
  let browser;
  try{browser=await engine.launch({headless:true,...(process.env.STUDY_DOWNLOADS?{downloadsPath:process.env.STUDY_DOWNLOADS}:{})});}catch(e){skipped.push(`${name}: ${e.message.split('\n')[0]}`);continue;}
  try {
    for(const viewport of [{width:1440,height:1100},{width:390,height:844}]) {
      const page=await browser.newPage({viewport,reducedMotion:'reduce',acceptDownloads:true});
      const errors=[];page.on('pageerror',e=>errors.push(e.message));
      await page.goto(url);
      await page.locator('.draft-card').last().waitFor();
      assert.equal(await page.locator('.family').count(),65);
      assert.equal(await page.locator('.draft-card').count(),257);
      assert.equal(await page.locator('.family-review').count(),65);
      assert.equal(await page.locator('#entry-total').textContent(),'257');
      assert.equal(await page.locator('.live-card').count(),36);
      assert.equal(await page.locator('#coverage-rows tr').count(),36);
      const audit=await page.evaluate(()=>{
        const d=DFGFamilyStudy,ids=d.families.flatMap(f=>f.variants.map(v=>v.id));
        return {unique:new Set(ids).size,counts:d.families.map(f=>f.variants.length),invalid:d.families.flatMap(f=>f.variants.filter(v=>v.attack<0||v.block<0||(v.routes||f.routes).some(c=>!d.colors.includes(c))))};
      });
      assert.equal(audit.unique,257);assert.ok(audit.counts.every(n=>n>=3));assert.deepEqual(audit.invalid,[]);
      const liveCheck=await page.evaluate(()=>DFGStudyLiveCatalog.cards.map(c=>{
        const el=document.querySelector(`[data-live-id="${c.id}"]`);
        return {id:c.id,color:el.dataset.color,name:el.querySelector('h4').textContent,text:el.querySelector('.card-rules p').textContent,
          attack:Number(el.querySelector('.stat-row strong').textContent),block:Number(el.querySelectorAll('.stat-row strong')[1].textContent),
          bonusVisible:el.querySelector('.card-footer').textContent.includes(c.bonus||'Unavailable'),reactionVisible:el.querySelector('.card-footer').textContent.includes(c.reaction||'Unavailable'),
          exact:el.dataset.color===c.color&&el.querySelector('h4').textContent===c.name&&el.querySelector('.card-rules p').textContent===c.ability};
      }));
      assert.ok(liveCheck.every(c=>c.exact&&c.bonusVisible&&c.reactionVisible),'Every current card renders its exact catalog fields');
      await page.locator('#coverage > details > summary').click();
      assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Open coverage table remains within viewport');
      await page.locator('#search').fill('zzzz-no-match');
      await page.locator('#coverage-rows [href="#F45"]').first().click();
      await page.locator('#F45 [data-live-id="14"]').waitFor();
      assert.equal(await page.locator('.family').count(),65,'Coverage links reveal filtered-out baselines');
      await page.locator('#coverage > details > summary').click();
      assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Page fits viewport');
      const overflowing=await page.locator('.draft-card').evaluateAll(cards=>cards.filter(c=>c.scrollWidth>c.clientWidth+1).map(c=>c.dataset.card));
      assert.deepEqual(overflowing,[],'No clipped card content');
      const checks=await page.evaluate(()=>{
        const f=DFGFamilyStudy.families;
        const run=(family,variant,input)=>DFGStudyEvaluate(f[family-1].variants[variant],{life:24,enemyAttack:4,enemyBlock:2,stamina:4,...input});
        return {
          healing:[0,2,4,6].map(a=>f[0].variants.slice(0,3).map(v=>DFGStudyEvaluate(v,{life:24,enemyAttack:a,enemyBlock:2,stamina:4}).life-24)),
          cap:run(1,0,{life:40,enemyAttack:0}).gained,
          revealSave:run(1,0,{life:2,enemyAttack:5}),
          noRevive:run(2,0,{life:2,enemyAttack:8,enemyBlock:0}),
          noHitNoHeal:run(2,2,{enemyAttack:0,enemyBlock:8}),
          cleanGuard:run(3,0,{enemyAttack:2}),
          cannotAfford:run(16,2,{stamina:3}),
          exactRead:run(13,1,{enemyAttack:3}),
          wrongRead:run(13,1,{enemyAttack:4}),
          threshold:run(20,2,{stamina:6}),
          pierceFloor:run(7,2,{enemyBlock:2})
        };
      });
      assert.deepEqual(checks.healing,[[4,3,2],[2,3,2],[0,1,2],[-2,-1,0]]);
      assert.equal(checks.cap,0);assert.equal(checks.revealSave.ko,false);assert.equal(checks.revealSave.life,1);
      assert.equal(checks.noRevive.ko,true);assert.equal(checks.noRevive.gained,0);assert.equal(checks.noRevive.dealt,5);
      assert.equal(checks.noHitNoHeal.gained,0);assert.equal(checks.cleanGuard.gained,0);
      assert.equal(checks.cannotAfford.available,false);assert.equal(checks.exactRead.attack,5);assert.equal(checks.wrongRead.attack,2);
      assert.equal(checks.threshold.attack,7);assert.equal(checks.pierceFloor.dealt,4);
      await page.locator('#F07 .family-review summary').click();
      assert.equal(await page.locator('#F07 .family-review li').count(),6,'First-draft comparison is preserved');
      await page.locator('#F07 .family-review summary').click();
      await page.locator('#search').fill('Covered Return');
      assert.equal(await page.locator('.family').count(),1);assert.equal(await page.locator('.draft-card').count(),6,'Sibling search keeps family together');
      await page.locator('[data-save="F01"]').click();
      assert.equal(await page.locator('#saved-count').textContent(),'1');
      await page.locator('#reset').click();await page.locator('#saved-filter').click();
      assert.equal(await page.locator('.family').count(),1);
      await page.reload();assert.equal(await page.locator('#saved-count').textContent(),'1','Shortlist persists');
      await page.locator('#hand').selectOption('Purple');
      assert.ok(await page.locator('.family').evaluateAll(els=>els.every(el=>{
        const f=DFGFamilyStudy.families.find(f=>f.id===el.id);return f.color==='Purple'||f.variants.some(v=>v.color==='Purple');
      })),'Hand filter retains complete families containing that hand');
      await page.locator('#scope').selectOption('Existing vocabulary');
      assert.equal(await page.locator('.scope-tag.new').count(),0);
      await page.locator('#search').fill('zzzzzzzz');assert.equal(await page.locator('#empty').isVisible(),true);
      await page.locator('#clear-empty').click();assert.equal(await page.locator('.family').count(),65);
      await page.locator('#lab-family').selectOption('F16');
      await page.locator('#stamina').fill('0');
      assert.equal(await page.locator('#lab-results tr.unavailable').count(),2);
      await page.locator('#lab-family').selectOption('F14');
      await page.locator('#enemy-bonus').check();
      assert.deepEqual(await page.locator('#lab-results tr td:nth-child(2)').allTextContents(),['3 / 7','3 / 6','3 / 5']);
      await page.locator('#lab-family').selectOption('F15');
      await page.locator('#own-bonus').check();
      assert.deepEqual(await page.locator('#lab-results tr td:nth-child(2)').allTextContents(),['2 / 2','4 / 2','5 / 2']);
      await page.locator('#own-bonus').uncheck();await page.locator('#enemy-bonus').uncheck();
      await page.locator('#life').fill('1');await page.locator('#enemy-attack').fill('12');
      assert.equal(await page.locator('#lab-results tr.best').count(),0,'No knocked-out version is highlighted as a lead');
      await page.locator('#life').fill('24');await page.locator('#enemy-attack').fill('4');
      await page.locator('#lab-family').selectOption('F01');
      assert.equal(await page.locator('#stamina-control').isVisible(),false);
      await page.locator('[data-lab="F02"]').click();assert.equal(await page.locator('#lab-family').inputValue(),'F02');
      await page.locator('#search').fill('Covered Return');
      await page.evaluate(()=>{location.hash='F40';});
      await page.locator('#F40').waitFor();assert.equal(await page.locator('.family').count(),65,'Permalink reveals filtered-out target');
      await page.locator('#reset').click();await page.locator('#lab-family').selectOption('F01');
      const [download]=await Promise.all([page.waitForEvent('download'),page.locator('#export-data').click()]);
      assert.equal(download.suggestedFilename(),'dfg-families-revision-3.json');
      let downloadText='';for await(const chunk of await download.createReadStream())downloadText+=chunk.toString();
      const exported=JSON.parse(downloadText);
      assert.equal(exported.revision,3);assert.equal(exported.entryCount,257);assert.equal(exported.coverage.length,36);
      assert.ok(exported.families.every(f=>f.review?.critique));
      assert.ok(exported.families.slice(0,40).every(f=>f.before.length===6));
      assert.ok(exported.families.slice(40).every(f=>f.before.length===0));
      await page.evaluate(()=>{location.hash='';scrollTo(0,0);});
      if(screenshots&&name==='chromium') {
        await page.screenshot({path:`${screenshots}/family-study-${viewport.width}.png`});
        await page.locator('#F01').scrollIntoViewIfNeeded();
        await page.screenshot({path:`${screenshots}/family-cards-${viewport.width}.png`});
        await page.locator('#F41').evaluate(el=>el.scrollIntoView({block:'start'}));
        await page.screenshot({path:`${screenshots}/family-current-${viewport.width}.png`});
      }
      assert.deepEqual(errors,[]);await page.close();
      console.log(`PASS ${name} ${viewport.width}: gallery, card overflow, payoff fixtures, filters, shortlist, permalink`);passed++;
    }
  } finally {await browser.close();}
}
console.log(JSON.stringify({passed,skipped},null,2));
if(!passed)process.exitCode=1;
