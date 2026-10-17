import { chromium, firefox, webkit } from 'playwright';
import assert from 'node:assert/strict';
import { mkdir } from 'node:fs/promises';

const url = process.env.DFG_URL || 'http://localhost/TCGEngine/DFGSim/';
const output = process.env.DFG_SCREENSHOTS;
if (output) await mkdir(output, { recursive: true });
let failures = 0, completed = 0;
for (const [name, engine] of Object.entries({ chromium, firefox, webkit })) {
  let browser;
  try { browser = await engine.launch({ headless: true }); }
  catch (error) { console.log(`${name}: unavailable: ${error.message.split('\n')[0]}`); continue; }
  try {
    for (const viewport of [{ width: 1600, height: 1000 }, { width: 1366, height: 768 }, { width: 1024, height: 600 }, { width: 768, height: 1024 }, { width: 390, height: 844 }]) {
      const page = await browser.newPage({ viewport, reducedMotion: 'reduce' });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(url);
      await page.locator('#setup[open]').waitFor();
      await page.locator('[name=seed]').fill('42');
      await page.locator('#setupForm [type=submit]').click();
      await page.locator('#setup[open]').waitFor({ state: 'detached' });
      await page.locator('#cards .card-tile').first().waitFor();
      const focalGeometry=()=>page.evaluate(()=>['.combat-layout','.arena-art','#timeline','#played-main-0','#played-main-1','#matchControls','#cards'].map(selector=>{
        const rect=document.querySelector(selector).getBoundingClientRect();
        return {selector,x:rect.x+scrollX,y:rect.y+scrollY,width:rect.width,height:rect.height};
      }));
      let initialGeometry;
      const assertLayout = async () => {
        const geometry=await focalGeometry();
        if(initialGeometry) for(let i=0;i<geometry.length;i++) for(const key of ['x','y','width','height']) assert.ok(Math.abs(geometry[i][key]-initialGeometry[i][key])<2,`${geometry[i].selector} ${key} stays fixed through phase changes (${geometry[i][key]} vs ${initialGeometry[i][key]})`);
        else initialGeometry=geometry;
        const layout = await page.evaluate(() => ({
          overflow: document.documentElement.scrollWidth > innerWidth,
          names: [...document.querySelectorAll('#fighters h2')].map(el => el.textContent),
          fighters: [...document.querySelectorAll('#fighters .fighter')].map(el => el.getBoundingClientRect().x),
          mains: [0, 1].map(seat => document.getElementById(`played-main-${seat}`).getBoundingClientRect().x),
          mainTops: [0, 1].map(seat => document.getElementById(`played-main-${seat}`).getBoundingClientRect().top),
          round: document.getElementById('roundNumber').textContent,
          health: [...document.querySelectorAll('.health-track')].map(el => el.getBoundingClientRect().top),
          statOverflow: [...document.querySelectorAll('#reveals .tile-stats')].some(el => el.scrollWidth > el.clientWidth + 1),
        }));
        assert.equal(layout.overflow, false, `horizontal overflow at ${viewport.width}`);
        assert.deepEqual(layout.names, ['YOU', 'SPARRING BOT']);
        assert.ok(layout.fighters[0] < layout.fighters[1]);
        assert.ok(layout.mains[0] < layout.mains[1]);
        assert.ok(Math.abs(layout.mainTops[0]-layout.mainTops[1])<2, 'main cards align');
        assert.ok(Number(layout.round) >= 1);
        assert.ok(Math.abs(layout.health[0] - layout.health[1]) < 2, 'health bars align');
        assert.equal(layout.statOverflow, false, 'played stats fit within their card');
      };
      await assertLayout();
      assert.equal(await page.locator('#fighters').evaluate(el=>getComputedStyle(el).overflowY),'visible','health area can overlap below its reserved row without a scrollbar');
      assert.equal(await page.locator('.fighter-color').count(),0);
      const healthColors=await page.evaluate(()=>{
        const original=state.players.map(p=>p.color),results=[];
        try {
          for(const color of Object.keys(styles)) {
            state.players.forEach(p=>p.color=color);render();
            results.push([...document.querySelectorAll('.fighter')].map(fighter=>{
              const probe=document.createElement('span');probe.style.color='var(--hand)';fighter.append(probe);
              const expected=getComputedStyle(probe).color;probe.remove();
              return {expected,border:getComputedStyle(fighter.querySelector('.health-track')).borderTopColor,fill:getComputedStyle(fighter.querySelector('.health-fill')).backgroundImage};
            }));
          }
        } finally {state.players.forEach((p,seat)=>p.color=original[seat]);render();}
        return results;
      });
      for(const colors of healthColors) for(const color of colors) {
        assert.equal(color.border,color.expected,'health border matches active hand');
        assert.ok(color.fill.includes(color.expected),'health fill matches active hand');
      }
      if(viewport.width>=1000) {
        const bottom=await page.locator('#cards .tile-face').evaluateAll(cards=>Math.max(...cards.map(c=>c.getBoundingClientRect().bottom)));
        assert.ok(bottom<=viewport.height,`hand cards fit within ${viewport.height}px viewport (bottom ${bottom})`);
        assert.ok(viewport.height-bottom<=18,`hand uses available height instead of leaving bottom whitespace (gap ${viewport.height-bottom})`);
      }
      assert.equal(await page.locator('.topbar').count(),0);
      assert.equal(await page.locator('.arena #matchMenu').count(),1);
      assert.equal(await page.locator('.combat-layout #timeline').count(),1);
      const phasePosition=await page.evaluate(()=>({phase:document.getElementById('timeline').getBoundingClientRect(),cards:document.getElementById('reveals').getBoundingClientRect()}));
      assert.ok(phasePosition.phase.bottom<=phasePosition.cards.top,'phase tracker sits above the main cards');
      assert.equal(await page.locator('.sidebar .action-panel').count(),0);
      assert.equal(await page.locator('.combat-layout #commit').count(),1);
      assert.equal(await page.locator('#commit').isDisabled(),true);
      const disabledBackground=await page.locator('#commit').evaluate(el=>getComputedStyle(el).backgroundImage);
      await page.locator('.commit-prompt').hover();
      await page.waitForFunction(()=>getComputedStyle(document.getElementById('commitHelp')).visibility==='visible');
      assert.match(await page.locator('#commitHelp').innerText(),/main card.*bonus burn/s);
      await page.mouse.move(0,0);
      await page.locator('.commit-prompt').focus();
      await page.waitForFunction(()=>getComputedStyle(document.getElementById('commitHelp')).visibility==='visible');
      assert.equal(await page.locator('#yourOrbs button').count(), 6);
      assert.equal(await page.locator('#cards .card-status').count(),0);
      assert.equal(await page.locator('#cards .playable').count(),6,'all available active-hand mains glow');
      assert.equal(await page.locator('#cards .playable .tile-face').first().evaluate(el=>getComputedStyle(el).animationName),'none','reduced-motion preference is respected');
      const liquidAnimations=()=>page.evaluate(()=>({health:getComputedStyle(document.querySelector('.health-fill'),'::after').animationName,orb:getComputedStyle(document.querySelector('.orb-fill'),'::after').animationName}));
      assert.deepEqual(await liquidAnimations(),{health:'none',orb:'none'},'arcade meters retain static scanlines with reduced motion');
      if(viewport.width===1600) {
        await page.emulateMedia({reducedMotion:'no-preference'});
        assert.equal(await page.locator('#cards .playable .tile-face').first().evaluate(el=>getComputedStyle(el).animationName),'playable-glow');
        assert.deepEqual(await liquidAnimations(),{health:'arcade-sweep',orb:'arcade-sweep'},'arcade meters use occasional light sweeps');
        assert.equal(await page.locator('.meter-change').count(),0,'no response animation on initial render');
        const liquidState=await page.evaluate(()=>{
          const saved=state;
          state=structuredClone(state);
          state.players[0].life=23;
          const removed=state.players[0].available.filter(id=>card(id).color===state.players[0].color).slice(0,3);
          state.players[0].available=state.players[0].available.filter(id=>!removed.includes(id));
          state.players[0].discarded.push(...removed);render();animateMeterChanges(saved,state);
          window.liquidSavedState=saved;
          return state.players[0].color;
        });
        const amounts=await page.evaluate(color=>({health:document.querySelector('.health-fill').style.width,orb:document.querySelector(`#your-orb-${color} .orb-fill`).style.height,count:document.querySelector(`#your-orb-${color} .orb-count`).textContent}),liquidState);
        assert.deepEqual(amounts,{health:'57.5%',orb:'50%',count:'3'},'liquid effects preserve actual health and inventory levels');
        assert.equal(await page.locator('.meter-change').count(),2,'only changed health and inventory meters react');
        assert.equal(await page.locator('.health-trail').count(),1,'health loss leaves one temporary damage trail');
        assert.equal(await page.locator('.health-trail').evaluate(el=>el.style.width),'100%','damage trail starts at the previous health level');
        assert.equal(await page.locator('.damage-number').innerText(),'−17');
        assert.equal(await page.locator('.health-track.damage-hit').count(),1);
        assert.equal(await page.locator('#cards.hand-impact').count(),1);
        const largeShake=await page.locator('.arena-art').evaluate(el=>parseFloat(el.style.getPropertyValue('--impact-x')));
        const largeHandShake=await page.locator('#cards').evaluate(el=>({x:parseFloat(el.style.getPropertyValue('--impact-x')),duration:parseFloat(el.style.getPropertyValue('--impact-duration'))}));
        assert.equal(await page.locator('.health-fill.meter-change').evaluate(el=>getComputedStyle(el,'::after').animationName),'arcade-response');
        if(output) await page.screenshot({path:`${output}/dfg-liquid-${name}.png`,fullPage:true});
        await page.waitForFunction(()=>document.querySelectorAll('.meter-change').length===0);
        await page.waitForFunction(()=>document.querySelectorAll('.health-trail').length===0);
        await page.waitForFunction(()=>document.querySelectorAll('.damage-number').length===0);
        await page.evaluate(()=>{state=window.liquidSavedState;delete window.liquidSavedState;render();});
        await page.evaluate(()=>animateMeterChanges(state,state));
        assert.equal(await page.locator('.meter-change').count(),0,'unchanged meters do not replay the response');
        assert.equal(await page.locator('.damage-number').count(),0,'unchanged state does not replay damage');
        await page.evaluate(()=>{const previous=structuredClone(state),next=structuredClone(state);next.players[0].life--;animateMeterChanges(previous,next);});
        const smallShake=await page.locator('.arena-art').evaluate(el=>parseFloat(el.style.getPropertyValue('--impact-x')));
        assert.ok(largeShake>smallShake,'larger hits produce more shake');
        const smallHandShake=await page.locator('#cards').evaluate(el=>parseFloat(el.style.getPropertyValue('--impact-x')));
        assert.ok(largeHandShake.x>smallHandShake*4,'heavy hits produce a much stronger hand shake');
        await page.waitForFunction(()=>document.querySelectorAll('.damage-number,.health-trail').length===0);
        await page.evaluate(()=>{const previous=structuredClone(state),next=structuredClone(state);next.players[0].life-=5;animateMeterChanges(previous,next);});
        const fiveDamageShake=await page.locator('#cards').evaluate(el=>({x:parseFloat(el.style.getPropertyValue('--impact-x')),duration:parseFloat(el.style.getPropertyValue('--impact-duration'))}));
        assert.ok(fiveDamageShake.x>=11&&fiveDamageShake.duration>=680,'five damage crosses into a hard, longer hand shake');
        assert.ok(largeHandShake.x>fiveDamageShake.x&&largeHandShake.duration>fiveDamageShake.duration,'larger hits continue to increase strength and duration');
        await page.waitForFunction(()=>document.querySelectorAll('.damage-number,.health-trail,.hand-impact').length===0);
        await page.emulateMedia({reducedMotion:'reduce'});
        await page.evaluate(()=>{const previous=structuredClone(state),next=structuredClone(state);next.players[0].life--;animateMeterChanges(previous,next);});
        assert.equal(await page.locator('.art-impact,.hand-impact,.health-track.damage-hit').count(),0,'reduced motion avoids shake and flashes');
        assert.equal(await page.locator('.damage-number').innerText(),'−1','reduced motion retains a readable damage number');
        await page.waitForFunction(()=>document.querySelectorAll('.damage-number').length===0);
      }
      assert.equal(await page.locator('#fighters .fighter').first().locator('#yourOrbs').count(), 1);
      const orbRows=await page.locator('.hand-orbs').evaluateAll(rows=>rows.map(row=>row.getBoundingClientRect().top));
      assert.ok(Math.abs(orbRows[0]-orbRows[1])<2, 'hand orbs mirror each other below health');
      await page.locator('#your-orb-Purple').click();
      assert.equal(await page.locator('#your-orb-Purple').getAttribute('aria-pressed'), 'true');
      assert.equal(await page.locator('#your-orb-Red').evaluate(el=>el.classList.contains('active-hand')), true);
      assert.equal(await page.locator('#cards .card-tile.purple').count(), 6);
      assert.equal(await page.locator('#cards .playable').count(),0,'inactive hands do not glow');
      assert.equal(await page.locator('#commit').isDisabled(), true);
      assert.equal(await page.locator('#your-orb-Purple').evaluate(el=>el===document.activeElement), true);
      await page.locator('#your-orb-Red').click();
      assert.equal(await page.locator('#fighters').innerText().then(text => /cards remaining|permanently burned/.test(text)), false);
      await page.evaluate(() => { state.players[0].advantages=Object.keys(styles); render(); });
      await assertLayout();
      await page.evaluate(() => { state.players[0].advantages=[]; render(); });
      await page.locator('#cards [data-hand-action]').nth(1).click();
      assert.equal(await page.locator('.commit-row #advantagePicker').count(),1);
      assert.match(await page.locator('#advantagePicker>summary').innerText(),/None held/);
      assert.equal(await page.locator('.token-spending').count(),0,'no full-width advantage pane');
      await page.locator('#advantagePicker>summary').click();
      assert.equal(await page.locator('#advantagePicker [data-advantage=Red]').isDisabled(),true);
      await page.keyboard.press('Escape');
      assert.equal(await page.locator('#advantagePicker').getAttribute('open'),null);
      await page.evaluate(()=>{state.players[0].advantages=['Red','Blue','Green'];render();});
      await page.locator('#advantagePicker>summary').click();
      await page.locator('#advantagePicker [data-advantage=Red]').click();
      assert.equal(await page.locator('#advantagePicker [data-advantage=Red]').getAttribute('aria-pressed'),'true');
      assert.notEqual(await page.locator('#advantagePicker').getAttribute('open'),null,'chooser remains open while selecting');
      await page.locator('#clear').click();
      await page.locator('#cards [data-hand-action]').nth(4).click();
      await page.locator('#advantagePicker>summary').click();
      await page.locator('#advantagePicker [data-advantage=Red]').click();
      await page.locator('#advantagePicker [data-advantage=Blue]').click();
      assert.equal(await page.locator('#advantagePicker [data-advantage=Green]').isDisabled(),true,'two-token limit remains enforced');
      assert.match(await page.locator('#advantagePicker>summary').innerText(),/2\/2/);
      await page.locator('#advantagePicker [data-advantage=Red]').click();
      assert.equal(await page.locator('#advantagePicker [data-advantage=Green]').isDisabled(),false);
      await page.locator('#clear').click();
      await page.evaluate(()=>{state.players[0].advantages=[];render();});
      await page.locator('#cards [data-hand-action]').first().click();
      await page.locator('#commit:enabled').waitFor();
      assert.notEqual(await page.locator('#commit').evaluate(el=>getComputedStyle(el).backgroundImage),disabledBackground,'ready commit has a distinct enabled color');
      assert.equal(await page.locator('#commitHelp').count(),0);
      assert.match(await page.locator('#reveals .reveal').first().innerText(), /Quick Jab/);
      assert.equal(await page.locator('#cards .main-selected.playable').count(),0);
      const bonusHighlights=await page.evaluate(()=>({actual:[...document.querySelectorAll('#cards .playable')].map(el=>Number(el.dataset.cardId)),expected:[...catalog.values()].filter(c=>c.color===state.players[0].color&&c.id!==selectedMain&&!!c.bonus).map(c=>c.id)}));
      assert.deepEqual(bonusHighlights.actual,bonusHighlights.expected,'only legal bonus burns glow after selecting a main');
      assert.equal(await page.locator('#reveals .played-details').count(), 0);
      assert.equal(await page.locator('#reveals .combat-slots').last().locator('.main-slot [data-inspect]').count(), 0, 'opponent commitment stays hidden');
      await page.locator('#cards [data-hand-action]').nth(1).click();
      assert.equal(await page.locator('#burns-0 .bonus-slot button').count(), 1);
      assert.equal(await page.locator('#cards .playable').count(),0,'no additional bonus burns glow once the bonus is filled');
      await page.locator('#burns-0 .bonus-slot button').click();
      assert.equal(await page.locator('#burns-0 .bonus-slot button').count(), 0, 'staged bonus can be undone');
      await page.locator('#clear').click();
      assert.equal(await page.locator('#commit').isDisabled(), true);
      await page.locator('#cards [data-hand-action]').first().click();
      await page.keyboard.press('Escape');
      await page.mouse.move(0, 0);
      if (output) await page.screenshot({ path: `${output}/dfg-arena-${name}-${viewport.width}.png`, fullPage: true });
      await page.locator('#commit').click();
      await page.locator('#pass:enabled').waitFor();
      await assertLayout();
      assert.equal(await page.locator('#phaseTitle').isVisible(),false,'reaction title removed');
      assert.equal(await page.locator('#phaseHint').isVisible(),false,'reaction instructions removed');
      assert.equal(await page.locator('#controls .selection-summary').count(),0,'no reaction status pane');
      assert.equal(await page.locator('#controls>p').count(),0,'no reaction footer');
      assert.match(await page.locator('.reaction-summary').innerText(),/\d\/2 passes · \d reactions/);
      const reactionHighlights=await page.evaluate(()=>({actual:[...document.querySelectorAll('#cards .playable')].map(el=>Number(el.dataset.cardId)),expected:state.reactionOptions}));
      assert.deepEqual(reactionHighlights.actual.slice().sort((a,b)=>a-b),reactionHighlights.expected.slice().sort((a,b)=>a-b),'only available reactions glow');
      const reactionLayouts=await page.evaluate(()=>{
        const saved=state, layouts=[];
        try {
          state=structuredClone(state);
          for(let count=0;count<=5;count++) {
            state.reactions=[];
            for(const seat of [0,1]) {
              const ids=[...catalog.values()].filter(c=>c.color===state.players[seat].color).map(c=>c.id);
              state.commitments[seat]={main:ids[0],bonuses:[]};
              state.players[seat].discarded=[];
              state.reactions.push(...ids.slice(1,count+1).map(card=>({seat,card})));
            }
            render();
            for(const seat of [0,1]) {
              const slots=document.querySelector(`#burns-${seat} .reaction-cards`);
              layouts.push({seat,count,played:slots.querySelectorAll('button').length,empty:slots.querySelectorAll('.burn-placeholder').length,overflow:document.documentElement.scrollWidth>innerWidth,positions:[...slots.children].map(el=>({x:el.getBoundingClientRect().x,y:el.getBoundingClientRect().y}))});
            }
          }
        } finally { state=saved;render(); }
        return layouts;
      });
      for(const layout of reactionLayouts) {
        assert.equal(layout.played,layout.count,'all reactions remain visible');
        assert.equal(layout.empty,1,'always one extra empty reaction slot');
        assert.equal(layout.overflow,false,'five reactions fit without horizontal overflow');
        for(let i=1;i<layout.positions.length;i++) {
          const previous=layout.positions[i-1], next=layout.positions[i];
          assert.ok(Math.abs(next.y-previous.y)<2,'all reactions stay on one row');
          assert.ok(layout.seat===0?next.x<previous.x:next.x>previous.x,'reactions and empty slot fill from center outward');
        }
      }
      await page.evaluate(()=>window.phaseLayoutSaved=state);
      try {
        for(const phase of ['cancelling','stripping','routing','directing','finished']) {
          await page.evaluate(phase=>{
            state=structuredClone(window.phaseLayoutSaved);state.phase=phase;
            state.cancelOptions=[state.commitments[0].main];state.stripOptions=Object.keys(styles);
            state.cutOptions=Object.keys(styles);state.forceOptions=Object.keys(styles);state.winner=0;
            state.players.forEach(p=>{p.nextAttack=2;p.nextBlockPenalty=2;p.burnLockedRound=state.round+1;p.pendingObjective={source:3,round:state.round+1,minimumDamage:4,color:'Green',requirement:'deal 4+ damage to earn Green advantage'};});
            render();
          },phase);
          await assertLayout();
          assert.equal(await page.locator('#fighters').evaluate(el=>getComputedStyle(el).overflowY),'visible','stacked status messages do not create a health-area scrollbar');
        }
      } finally {await page.evaluate(()=>{state=window.phaseLayoutSaved;delete window.phaseLayoutSaved;render();});}
      const reaction=page.locator('#cards button[aria-label*="Click to burn reaction"]').first();
      if(await reaction.count()) {
        await reaction.click();
        await page.locator('#burns-0 .reaction-slots button').first().waitFor();
        await page.locator('#pass:enabled').waitFor();
        await assertLayout();
        await page.keyboard.press('Escape');
        await page.mouse.move(0, 0);
        if(output && [1600,390].includes(viewport.width)) await page.screenshot({path:`${output}/dfg-reactions-${name}-${viewport.width}.png`,fullPage:true});
      }
      await page.locator('#pass').click();
      await page.locator('[data-switch]').first().waitFor();
      await assertLayout();
      assert.equal(await page.locator('#phaseHint').isVisible(),false,'switch instructions removed');
      const switches=await page.evaluate(()=>({actual:[...document.querySelectorAll('.switch-orbs [data-switch]')].map(el=>el.dataset.switch),expected:state.switchOptions}));
      assert.deepEqual(switches.actual,switches.expected,'only legal next hands appear as orbs');
      assert.equal(await page.locator('.switch-orbs .orb-sphere').count(),switches.expected.length);
      assert.equal(await page.locator('#controls .switch-button').count(),0);
      if(output && [1600,390].includes(viewport.width)) await page.screenshot({path:`${output}/dfg-switch-${name}-${viewport.width}.png`,fullPage:true});
      await page.locator('[data-switch]').first().click();
      await page.waitForFunction(() => document.getElementById('roundNumber').textContent === '2');
      await assertLayout();
      await page.locator('#opponentOrbs button').first().click();
      await page.locator('#opponentHandPopup').waitFor();
      await page.keyboard.press('Escape');
      assert.equal(await page.locator('#showRules').isVisible(), false);
      await page.locator('#matchMenu summary').click();
      await page.locator('#showRules').click();
      await page.locator('#rulesPopup').waitFor();
      assert.equal(await page.locator('#matchMenu').getAttribute('open'), null);
      await page.keyboard.press('Escape');
      assert.equal(await page.locator('#matchMenu summary').evaluate(el => el === document.activeElement), true);
      await page.locator('#matchMenu summary').click();
      await page.keyboard.press('Escape');
      assert.equal(await page.locator('#matchMenu').getAttribute('open'), null);
      await page.locator('#matchMenu summary').click();
      await page.locator('#showLog').click();
      await page.locator('#fightLog[open]').waitFor();
      assert.ok(await page.locator('#fightLog .log-entry').count()>0);
      assert.equal(await page.locator('.history-panel').count(),0,'fight log is no longer below the hand');
      await page.locator('#closeLog').click();
      assert.equal(await page.locator('#fightLog').getAttribute('open'),null);
      await page.locator('#matchMenu summary').click();
      await page.locator('#showLog').click();
      await page.keyboard.press('Escape');
      await page.locator('#fightLog[open]').waitFor({state:'detached'});
      assert.equal(await page.locator('#fightLog').getAttribute('open'),null);
      assert.deepEqual(errors, []);
      assert.equal(await page.locator('#error').isVisible(), false);
      console.log(`${name} ${viewport.width}: layout, selection, commit, damage, next round, inventory and rules passed`);
      completed++;
      await page.close();
    }
  } catch (error) { failures++; console.error(`${name}: ${error.stack}`); }
  finally { await browser.close(); }
}
process.exitCode = failures || !completed ? 1 : 0;
