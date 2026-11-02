import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {mkdir} from 'node:fs/promises';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit}=require('playwright');
const base=process.env.ADVENTURE_URL||'http://localhost/TCGEngine/DFGSim/adventure/';
const shots=process.env.ADVENTURE_SCREENSHOTS;if(shots)await mkdir(shots,{recursive:true});
for(const spec of [
  {name:'chromium',engine:chromium,width:1440,motion:'no-preference'},
  {name:'chromium',engine:chromium,width:390,motion:'no-preference'},
  {name:'chromium',engine:chromium,width:390,motion:'reduce'},
  {name:'webkit',engine:webkit,width:1440,motion:'reduce'},
  {name:'webkit-motion',engine:webkit,width:1440,motion:'no-preference'},
  {name:'chromium-fallback',engine:chromium,width:1440,motion:'no-preference',failMedia:true}
].filter(spec=>!process.env.ADVENTURE_AMBIENT_CASE||spec.name===process.env.ADVENTURE_AMBIENT_CASE)){
  const browser=await spec.engine.launch({headless:true});
  try{
    const context=await browser.newContext({viewport:{width:spec.width,height:1000},reducedMotion:spec.motion});
    let data=await (await context.request.get(new URL('api.php',base).href)).json();
    const act=async action=>{const r=await context.request.post(new URL('api.php',base).href,{data:{command:'action',token:data.token,runId:data.journey.id,revision:data.journey.revision,action}});assert.equal(r.status(),200,await r.text());data=await r.json();};
    const talk=async id=>{
      const e=data.journey.entities[id],start=data.journey.position,q=[[start.x,start.y]],parents=new Map([[q[0].join(','),null]]);
      const blocked=(x,y)=>x<0||y<0||x>=24||y>=16||data.map.blocked.some(([a,b,w,h])=>x>=a&&x<a+w&&y>=b&&y<b+h)||Object.values(data.journey.entities).some(n=>['npc','champion'].includes(n.kind)&&n.x===x&&n.y===y);
      let path=null;
      for(let i=0;i<q.length;i++){
        const pos=q[i];if(Math.abs(pos[0]-e.x)+Math.abs(pos[1]-e.y)<=1){path=[];let at=pos;while(parents.get(at.join(','))!==null){path.push(at);at=parents.get(at.join(','));}path.reverse();break;}
        for(const [dx,dy]of [[0,1],[1,0],[0,-1],[-1,0]]){const next=[pos[0]+dx,pos[1]+dy],key=next.join(',');if(!parents.has(key)&&!blocked(...next)){parents.set(key,pos);q.push(next);}}
      }
      assert.ok(path,'Reachable '+id);if(path.length)await act({type:'move',path});await act({type:'interact',id});
    };
    await talk('mailbox');await talk('quay-path');await act({type:'door',id:'quay-path'});assert.equal(data.journey.zone,'riverside');
    const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));page.setDefaultTimeout(15000);
    if(spec.failMedia)await page.route('**/riverside-section-registered-v3.mp4',r=>r.abort());
    await page.goto(base);await page.locator('#loading[hidden]').waitFor({state:'attached'});
    const video=page.locator('video[data-ambient-section="riverside"]');
    await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));
    if(spec.width===390)assert.equal(await video.count(),0,'No download while river is off-screen or reduced motion is on');
    await page.locator('#overview').click();
    if(spec.motion==='reduce'){
      await page.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));
      assert.equal(await video.count(),0,'Reduced motion never loads a clip');
    }else if(spec.failMedia){
      await page.waitForFunction(()=>document.querySelector('video[data-ambient-section]')?.error);
      assert.equal(await page.locator('#worldError').isVisible(),false,'Media failure does not interrupt the adventure');
    }else{
      await page.waitForFunction(()=>{const v=document.querySelector('video[data-ambient-section]');return v&&!v.paused&&v.readyState>=2;});
      const before=await video.evaluate(v=>v.currentTime);await page.waitForFunction(t=>document.querySelector('video[data-ambient-section]').currentTime!==t,before);
      await page.locator('[data-panel="journal"]').click();await page.waitForFunction(()=>document.querySelector('video[data-ambient-section]').paused);
      await page.locator('#closePanel').click();await page.waitForFunction(()=>!document.querySelector('video[data-ambient-section]').paused);
      if(shots&&spec.width===1440)await page.screenshot({path:`${shots}/adventure-live-riverside.png`});
      await page.emulateMedia({reducedMotion:'reduce'});await page.waitForFunction(()=>document.querySelector('video[data-ambient-section]').paused);
      await page.emulateMedia({reducedMotion:'no-preference'});await page.waitForFunction(()=>!document.querySelector('video[data-ambient-section]').paused);
      if(spec.width===390){await page.locator('#overview').click();await page.waitForFunction(()=>document.querySelector('video[data-ambient-section]').paused);}
    }
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false);
    assert.deepEqual(errors,[]);console.log(`PASS ${spec.name} ${spec.width} ${spec.motion}: visible playback / suspension / static fallback`);
  }finally{await browser.close();}
}
