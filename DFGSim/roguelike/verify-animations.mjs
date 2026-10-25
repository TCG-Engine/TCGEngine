import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit}=require('playwright');
const base=process.env.ROGUE_URL||'http://localhost/TCGEngine/DFGSim/roguelike/';
const shots=process.env.ROGUE_SCREENSHOTS;
if(shots)await mkdir(shots,{recursive:true});
let cases=0;
const keys=process.env.ROGUE_ANIMATION_KEYS?.split(',')||['fox','tortoise','tengu','dragon'];
for(const [name,engine] of Object.entries({chromium,webkit})){
  const browser=await engine.launch({headless:true});
  try{
    const seedContext=await browser.newContext();
    const get=async()=> (await seedContext.request.get(new URL('api.php',base).href)).json();
    let data=await get();
    const send=async(command,action=null)=>{
      const response=await seedContext.request.post(new URL('api.php',base).href,{data:{command,action,token:data.token,runId:data.run?.id??null,revision:data.run?.revision??null}});
      assert.equal(response.status(),200);data=await response.json();
    };
    await send('new');await send('action',{type:'starter',item:'jade-vest'});await send('action',{type:'travel',node:'0-0',color:'Red'});
    await seedContext.close();
    for(const viewport of [{width:1440,height:1000},{width:390,height:844}]){
      const reduced=viewport.width<700;
      for(const key of keys){
        const fixture=structuredClone(data);fixture.run.enemy=key;
        // Art-only enemies can exercise the shared fight renderer using a fixture moveset.
        const moveset=fixture.enemies[key]?key:'fox';
        fixture.enemies[key]??={...fixture.enemies.fox,name:key.replaceAll('-',' ')};
        const clips=fixture.animations[key];assert.ok(clips?.knockout?.finalSrc,`${key} has a complete animation set`);
        const enemy=fixture.run.battle.players[1],npc=fixture.enemies[key];
        enemy.deck=fixture.cards.filter(c=>c.enemy===moveset).map(c=>c.id);enemy.available=[...enemy.deck];enemy.discarded=[];enemy.life=enemy.maxLife=npc.life;enemy.color=npc.color;
        const context=await browser.newContext({viewport,reducedMotion:reduced?'reduce':'no-preference'}),page=await context.newPage(),errors=[];
        page.on('pageerror',e=>errors.push(e.message));
        await page.route('**/roguelike/api.php',route=>route.fulfill({json:fixture}));
        await page.goto(new URL('../index.php?roguelike=1',base).href);await page.locator('#gameView:not([hidden])').waitFor();
        await page.waitForFunction(()=>document.querySelector('.arena-fighter-opponent')?.naturalWidth>0);
        const idle=await page.locator('.arena-fighter-opponent').getAttribute('src');assert.equal(idle,clips.idle.src);
        assert.equal(await page.locator('.arena-fighter-opponent.rogue-creature').count(),0,'Generated clips replace temporary CSS motions');
        if(reduced)assert.ok((await page.locator('.arena-fighter-opponent').evaluate(e=>e.currentSrc)).includes(`${key}-still.webp`));
        if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-${key}-idle.png`});
        for(const [kind,damage] of [['block',0],['light',1],['medium',3],['heavy',5]]){
          await page.evaluate(damage=>DFGFighterReactions.play(1,damage),damage);
          if(reduced){assert.equal(await page.locator('.arena-fighter-opponent[data-reaction]').count(),0);continue;}
          await page.waitForFunction(kind=>document.querySelector('.arena-fighter-opponent')?.dataset.reaction===kind&&document.querySelector('.arena-fighter-opponent').naturalWidth>0,kind);
          const current=await page.locator('.arena-fighter-opponent').getAttribute('src');
          assert.ok(current.startsWith('blob:')||current.includes(`${key}-${kind}.webp`));
          // A normal UI rerender must not replace or restart an active reaction.
          await page.evaluate(()=>render());assert.equal(await page.locator('.arena-fighter-opponent').getAttribute('data-reaction'),kind);
          await page.waitForFunction(()=>!document.querySelector('.arena-fighter-opponent')?.dataset.reaction,null,{timeout:6000});
          assert.equal(await page.locator('.arena-fighter-opponent').getAttribute('src'),idle);
        }
        await page.evaluate(()=>DFGFighterReactions.syncOutcome(0,{animate:true}));
        await page.waitForFunction(()=>document.querySelector('.arena-fighter-opponent')?.dataset.reactionState==='held',null,{timeout:6000});
        const held=await page.locator('.arena-fighter-opponent').getAttribute('src');assert.ok(held.startsWith('blob:')||held.includes(`${key}-knockout-final.webp`));
        await page.evaluate(()=>{render();DFGFighterReactions.syncOutcome(0,{animate:true});});
        assert.equal(await page.locator('.arena-fighter-opponent').getAttribute('data-reaction-state'),'held','Repeated state updates do not replay knockout');
        if(shots)await page.screenshot({path:`${shots}/${name}-${viewport.width}-${key}-knockout.png`});
        await page.evaluate(()=>DFGFighterReactions.reset());assert.equal(await page.locator('.arena-fighter-opponent[data-reaction]').count(),0);
        assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);assert.deepEqual(errors,[]);
        await context.close();cases++;
        console.log(`PASS ${name} ${viewport.width} ${key}: idle, four reactions, held knockout, reset${reduced?', reduced motion':''}`);
      }
    }
  }finally{await browser.close();}
}
console.log(`PASS ${cases} animation browser cases`);
