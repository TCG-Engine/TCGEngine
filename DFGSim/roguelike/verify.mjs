import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,firefox,webkit}=require('playwright');
const url=process.env.ROGUE_URL||'http://localhost/TCGEngine/DFGSim/roguelike/';
const shots=process.env.ROGUE_SCREENSHOTS;
if(shots)await mkdir(shots,{recursive:true});
let passed=0;const skipped=[];
for(const [name,engine] of Object.entries({chromium,firefox,webkit})){
  let browser;try{browser=await engine.launch({headless:true});}catch(e){skipped.push(`${name}: ${e.message.split('\n')[0]}`);continue;}
  try{for(const viewport of [{width:1440,height:1000},{width:390,height:844}]){
    const context=await browser.newContext({viewport,reducedMotion:'reduce'}),page=await context.newPage(),errors=[];
    page.on('pageerror',e=>errors.push(e.message));
    const read=async()=>{const r=await page.request.get(new URL('api.php',url).href);assert.equal(r.status(),200);return r.json();};
    const fit=async()=>assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Fits viewport');
    const click=async selector=>{await page.locator(selector).first().click();await page.waitForFunction(()=>!document.body.classList.contains('busy')&&(typeof busy==='undefined'||!busy));assert.equal(await page.locator('#error').isVisible(),false);};
    await page.goto(url);await page.locator('[data-new]').waitFor();await fit();
    if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-welcome.png`});
    await click('[data-new]');assert.equal((await read()).run.deck.length,18);
    await click('[data-starter="jade-vest"]');await page.locator('.path-map').waitFor();await fit();
    if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-map.png`});
    await click('[data-travel="0-0"]');await page.locator('#gameView:not([hidden])').waitFor();
    let state=await read();const first=state;
    assert.equal(state.run.battle.commitments[1].hidden,true);
    assert.equal('main' in state.run.battle.commitments[1],false);
    assert.equal('seed' in state.run,false);assert.equal('initialSeed' in state.run,false);
    assert.equal('seed' in state.run.battle,false);assert.equal('initialSeed' in state.run.battle,false);
    assert.equal(state.run.battle.players[1].deck.length,18);
    await click('[data-browse="Blue"]');assert.equal(await page.locator('#cards .card-tile').count(),3,'Only owned moves appear in the shared hand browser');assert.equal(await page.locator('#cards .playable').count(),0,'Inactive hand is preview only');
    await click('[data-browse="Red"]');await click('#hand-100');await fit();
    if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-fight.png`});
    await click('#commit');state=await read();assert.notEqual(state.run.revision,first.run.revision);
    const post=async payload=>page.request.post(new URL('api.php',url).href,{data:payload});
    const envelope={command:'action',token:state.token,runId:state.run.id,revision:state.run.revision,action:{type:'battle',move:{type:'pass'}}};
    assert.equal((await post({...envelope,token:'bad'})).status(),403);
    assert.equal((await post({...envelope,revision:first.run.revision})).status(),409);
    assert.equal((await read()).run.revision,state.run.revision,'Rejected requests leave run intact');
    // Drive legal UI choices through two exchanges; server controls every enemy action.
    for(let i=0;i<15;i++){
      state=await read();const b=state.run.battle;if(state.run.phase!=='fight'||b.round>=3)break;
      if(b.phase==='reactions')await click('#pass');
      else if(b.phase==='switching')await click('[data-switch]');
      else if(b.phase==='planning'){
        const invalid=await post({...envelope,revision:state.run.revision,action:{type:'battle',move:{type:'commit',main:1,bonuses:[],advantages:[]}}});
        assert.equal(invalid.status(),400,'Cannot play unowned core card');
        await click('#cards .playable [data-hand-action]');await click('#commit');
      }else throw new Error(`Unexpected starter phase ${b.phase}`);
    }
    const saved=(await read()).run;await page.reload();await page.locator('#gameView:not([hidden])').waitFor();
    assert.equal((await read()).run.id,saved.id);assert.equal((await read()).run.revision,saved.revision);
    await page.locator('#matchMenu summary').click();await page.locator('#showRules').click();assert.equal(await page.locator('#rulesPopup').isVisible(),true);await page.locator('#rulesPopup button').first().click();
    assert.equal(await page.locator('.rogue-bar-break').count(),1,'Run armor extends the existing life bar');
    assert.equal(await page.locator('.arena-fighter-opponent').getAttribute('data-creature'),'fox');
    assert.ok((await page.locator('.fighter .life').last().textContent()).includes('/ 18'),'Enemy uses its own life maximum');
    await page.goto(new URL('?overview=1',url).href);await page.locator('#journal-button').click();assert.equal(await page.locator('#journal').isVisible(),true);await page.locator('[data-close="journal"]').click();
    await fit();assert.deepEqual(errors,[]);
    for(const key of ['fox','tortoise','tengu','dragon']){
      const fixture=structuredClone(first);fixture.run.enemy=key;
      const npc=fixture.enemies[key],enemy=fixture.run.battle.players[1];
      enemy.deck=fixture.cards.filter(c=>c.enemy===key).map(c=>c.id);enemy.available=[...enemy.deck];enemy.discarded=[];enemy.color=npc.color;enemy.life=enemy.maxLife=npc.life;
      const preview=await context.newPage();
      await preview.route('**/roguelike/api.php',route=>route.fulfill({json:fixture}));
      await preview.goto(new URL('../index.php?roguelike=1',url).href);await preview.locator('#gameView:not([hidden])').waitFor();
      await preview.waitForFunction(()=>document.querySelector('.arena-fighter-opponent')?.naturalWidth>0);
      const animation=fixture.animations?.[key],expected=animation?.knockout?.finalSrc?animation.idle.src:`roguelike/assets/${key}-v2.png`;
      assert.equal(await preview.locator('.arena-fighter-opponent').getAttribute('src'),expected);
      assert.ok((await preview.locator('.arena-fighter-player').getAttribute('src')).includes('fighter-blue-idle'),'Existing player and animation remain in use');
      if(shots)await preview.screenshot({path:`${shots}/${name}-${viewport.width}-${key}-shared.png`});
      if(key==='fox'){
        fixture.run.phase='cardReward';fixture.run.battle.phase='finished';fixture.run.battle.winner=0;fixture.run.reward={cards:[1,200,202],armor:[]};
        await preview.reload();await preview.locator('#playAgain').waitFor();assert.equal(await preview.locator('#playAgain').textContent(),'Claim rewards');
        await preview.locator('#playAgain').click();await preview.locator('[data-reward-card]').first().waitFor();
      }
      await preview.close();
    }
    // Snapshot-driven rendering checks complement the authoritative progression tests.
    // Only this isolated page mocks the API; no fixture endpoints enter the game.
    for(const phase of ['cardReward','armorReward','camp','shrine','shop','dead','victory']){
      const fixture=structuredClone(first);fixture.run.phase=phase;fixture.run.life=17;
      fixture.run.reward={cards:[1,200,202],armor:['echo-wraps','rally-wraps','iron-crown']};
      fixture.run.inventory=['jade-vest','echo-wraps'];fixture.run.removeOptions=fixture.run.deck;
      fixture.run.shop={cards:[1,200,202],armor:['echo-wraps','rally-wraps','iron-crown'],bought:[]};
      const preview=await context.newPage(),previewErrors=[];preview.on('pageerror',e=>previewErrors.push(e.message));
      const commands=[];
      await preview.route('**/roguelike/api.php',route=>{
        if(route.request().method()==='POST')commands.push(route.request().postDataJSON());
        return route.fulfill({json:fixture});
      });
      await preview.goto(url);await preview.locator('.room h1').waitFor();
      assert.equal(await preview.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,`${phase} fits viewport`);
      const selector={cardReward:'[data-reward-card="200"]',armorReward:'[data-reward-armor="echo-wraps"]',camp:'[data-rest]',shrine:'[data-leave]',shop:'[data-buy="200"]',dead:'[data-new]',victory:'[data-new]'}[phase];
      await preview.locator(selector).click();await preview.waitForFunction(()=>!document.body.classList.contains('busy')&&(typeof busy==='undefined'||!busy));
      assert.equal(commands.length,1,`${phase} control dispatches one command`);
      if(phase==='cardReward')assert.equal(commands[0].action.card,200);
      if(phase==='armorReward')assert.equal(commands[0].action.item,'echo-wraps');
      if(phase==='shop')assert.equal(commands[0].action.id,200);
      if(shots&&['cardReward','armorReward','victory'].includes(phase))await preview.screenshot({path:`${shots}/${name}-${viewport.width}-${phase}.png`});
      assert.deepEqual(previewErrors,[]);await preview.close();
    }
    await context.close();passed++;
  }}finally{await browser.close();}
}
assert.ok(passed>=2,'At least one engine must run both viewports');
console.log(JSON.stringify({passed,skipped},null,2));
