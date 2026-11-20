import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
import {readFile,mkdir} from 'node:fs/promises';
import {execFileSync} from 'node:child_process';
const require=createRequire(new URL('../../DevTools/ui-harness/package.json',import.meta.url));
const {chromium,webkit}=require('playwright');
const base='http://localhost/TCGEngine/DFGSim/adventure/';
const manifest=JSON.parse(await readFile(new URL('./audio/generation-manifest.json',import.meta.url),'utf8'));
const effects=JSON.parse(await readFile(new URL('./audio/effects/generation-manifest.json',import.meta.url),'utf8'));
const loopFixture=execFileSync('ffmpeg',['-hide_banner','-loglevel','error','-i',new URL('./audio/sanctuary-v1.mp3',import.meta.url).pathname.replace(/^\/([A-Z]:)/,'$1'),'-t','6','-f','mp3','pipe:1'],{maxBuffer:2000000});
for(const [name,engine] of Object.entries({chromium,webkit})){
 const browser=await engine.launch({headless:true});
 try{
  const context=await browser.newContext(),page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto(base);await page.locator('#loading[hidden]').waitFor({state:'attached'});
  assert.equal((await page.evaluate(()=>DFGSoundtrack.status)).enabled,false,'Opt-in audio');
  if(!(await page.evaluate(()=>DFGSoundtrack.status.supported))){assert.equal(await page.locator('[data-music-toggle]').isDisabled(),true);console.log(`SKIP ${name} playback: this runtime omits Web Audio; graceful unavailable controls verified`);await context.close();continue;}
  // Decode every actual delivered file, not only mocked player fixtures.
  const decoded=await page.evaluate(async tracks=>{const c=new(window.AudioContext||window.webkitAudioContext)(),result=[];for(const t of tracks){const r=await fetch('adventure/audio/'+t.file);if(!r.ok)throw Error('Missing '+t.id);const b=await c.decodeAudioData(await r.arrayBuffer());result.push({id:t.id,duration:b.duration,channels:b.numberOfChannels});}await c.close();return result;},manifest.tracks);
  assert.equal(decoded.length,11);for(const t of decoded){assert.ok(t.duration>60&&t.duration<84,t.id);assert.equal(t.channels,2);}
  const fxDecoded=await page.evaluate(async tracks=>{const c=new(window.AudioContext||window.webkitAudioContext)(),result=[];for(const t of tracks){const r=await fetch('adventure/audio/effects/'+t.file);if(!r.ok)throw Error('Missing '+t.id);const b=await c.decodeAudioData(await r.arrayBuffer());const samples=b.getChannelData(0);let peak=0;for(const sample of samples)peak=Math.max(peak,Math.abs(sample));result.push({id:t.id,duration:b.duration,peak});}await c.close();return result;},effects.sounds);assert.equal(fxDecoded.length,23);for(const t of fxDecoded){assert.ok(t.duration>0&&t.duration<3,t.id);assert.ok(t.peak>.001&&t.peak<1,t.id+' has audible unclipped samples');}
  // Shortened real audio exercises overlap scheduling without an 80-second test wait.
  await page.route('**/adventure/audio/*.mp3',r=>r.fulfill({contentType:'audio/mpeg',body:loopFixture}));
  await page.locator('[data-music-toggle]').click();await page.waitForFunction(()=>DFGSoundtrack.status.playing==='courtyard');
  assert.equal((await page.evaluate(()=>DFGSoundtrack.status)).contextState,'running');
  await page.evaluate(()=>DFGSoundtrack.select('sanctuary',{immediate:true}));await page.waitForFunction(()=>DFGSoundtrack.status.playing==='sanctuary');assert.ok((await page.evaluate(()=>DFGSoundtrack.status)).voices>=2,'Themes overlap during transition');
  await page.evaluate(()=>{DFGSoundtrack.select('market');DFGSoundtrack.select('grove');DFGSoundtrack.select('terrace');});await page.waitForFunction(()=>DFGSoundtrack.status.playing==='terrace');await page.waitForTimeout(7500);
  let status=await page.evaluate(()=>DFGSoundtrack.status);assert.equal(status.playing,'terrace');assert.ok(status.voices>=1&&status.voices<=2,'Looping does not accumulate sources');
  await page.locator('.dfg-audio-controls summary').click();await page.locator('[aria-label="Music volume"]').fill('25');assert.equal(await page.evaluate(()=>JSON.parse(localStorage.getItem('dfg-adventure-audio')).volume),.25);
  await page.locator('[data-music-toggle]').click();await page.waitForTimeout(400);assert.equal((await page.evaluate(()=>DFGSoundtrack.status)).voices,0,'Mute stops all sources');
  await page.locator('[data-music-toggle]').click();await page.waitForFunction(()=>DFGSoundtrack.status.playing==='terrace');
  const effectsBefore=(await page.evaluate(()=>DFGSoundtrack.status)).effectsPlayed;await page.evaluate(()=>DFGSoundtrack.effect('hit-medium',{seat:1}));await page.waitForFunction(n=>DFGSoundtrack.status.effectsPlayed>n,effectsBefore);
  await page.locator('[data-effects-toggle]').click();const muted=(await page.evaluate(()=>DFGSoundtrack.status)).effectsPlayed;await page.evaluate(()=>DFGSoundtrack.effect('hit-heavy'));assert.equal((await page.evaluate(()=>DFGSoundtrack.status)).effectsPlayed,muted);assert.equal((await page.evaluate(()=>DFGSoundtrack.status)).playing,'terrace','Effects mute leaves music running');await page.locator('[data-effects-toggle]').click();await page.locator('[aria-label="Effects volume"]').fill('35');assert.equal(await page.evaluate(()=>JSON.parse(localStorage.getItem('dfg-adventure-audio')).effectsVolume),.35);
  // Reach a real challenge through the normal API; test the actual persistent arena.
  let data=await (await context.request.get(base+'api.php')).json();
  const act=async action=>{const r=await context.request.post(base+'api.php',{data:{command:'action',token:data.token,runId:data.journey.id,revision:data.journey.revision,action}});assert.equal(r.status(),200);data=await r.json();};
  await act({type:'move',path:[[4,14],[3,14]]});await act({type:'interact',id:'mailbox'});await act({type:'close'});await act({type:'move',path:[[4,14],[5,14],[5,13],[5,12],[5,11],[5,10]]});await act({type:'interact',id:'iddin'});
  await page.reload();await page.locator('#loading[hidden]').waitFor({state:'attached'});await page.locator('[data-dialog-action="challenge"]').click();const arena=page.frameLocator('#adventureBattle');await arena.locator('#gameView:not([hidden])').waitFor();
  await arena.locator('[data-music-toggle]').click(); // Disable saved preference, then enable inside the arena.
  await arena.locator('[data-music-toggle]').click();await page.waitForFunction(()=>DFGSoundtrack.status.playing==='iddin');
  const countBefore=(await page.evaluate(()=>DFGSoundtrack.status)).effectsPlayed;await arena.locator('#hand-1').click();await page.waitForFunction(n=>DFGSoundtrack.status.effectsPlayed>n,countBefore);await arena.locator('#commit').click();await page.waitForFunction(n=>DFGSoundtrack.status.effectsPlayed>n+1,countBefore);
  const frame=page.frames().find(f=>f.url().includes('embedded=1'));assert.equal(await frame.evaluate(()=>DFGSoundtrack===parent.DFGSoundtrack),true,'Arena shares parent transport');
  await frame.evaluate(()=>{window.audioContextBefore=DFGSoundtrack;});
  await arena.locator('#matchMenu summary').click();await arena.locator('#goHome').click();await page.locator('#adventureBattle[hidden]').waitFor({state:'attached'});await page.waitForFunction(()=>DFGSoundtrack.status.playing!=='iddin');assert.equal((await page.evaluate(()=>DFGSoundtrack.status)).contextState,'running','Return does not destroy transport');
  await page.getByRole('button',{name:'Resume your challenge →',exact:true}).click();await arena.locator('#gameView:not([hidden])').waitFor();await page.waitForFunction(()=>DFGSoundtrack.status.playing==='iddin');
  await arena.locator('#matchMenu summary').click();await arena.locator('#resign').click();await arena.locator('#playAgain').waitFor();await arena.locator('#playAgain').click();await page.locator('#adventureBattle[hidden]').waitFor({state:'attached'});await page.waitForFunction(()=>DFGSoundtrack.status.playing==='sanctuary');
  assert.deepEqual(errors,[]);
  console.log(`PASS ${name}: 11 themes + 23 effects decode, effects mute/volume, real card swipe/commit sounds, shared arena transport, loops, crossfades, defeat → sanctuary`);
  await context.close();
 }finally{await browser.close();}
}
