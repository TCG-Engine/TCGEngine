'use strict';
// One Web Audio transport survives the map/arena transition in the adventure shell.
window.DFGSoundtrack=(()=>{
  if(window.parent!==window&&window.parent.location.origin===location.origin&&window.parent.DFGSoundtrack)return window.parent.DFGSoundtrack;
  const supported=!!(window.AudioContext||window.webkitAudioContext);
  const tracks={courtyard:['A Tablet at Your Door','courtyard-v1.mp3'],sanctuary:['A Cushion for Tomorrow','sanctuary-v1.mp3'],market:['Clay, Coins and Conversation','market-v1.mp3'],canal:['Where the Reeds Bend','canal-v1.mp3'],grove:['The Cedar Remembers','grove-v1.mp3'],terrace:['Lessons Above the City','terrace-v1.mp3'],temple:['The Court Beneath Heaven','temple-v1.mp3'],iddin:['The Reed-Court Lesson','iddin-v1.mp3'],imdugud:['Wings Above the Terrace','imdugud-v1.mp3'],huwawa:['Seven Rings of Cedar','huwawa-v1.mp3'],'bull-of-heaven':['The Horizon Takes a Step','bull-of-heaven-v1.mp3']};
  let prefs={enabled:false,volume:.45,effectsEnabled:true,effectsVolume:.65};try{prefs={...prefs,...JSON.parse(localStorage.getItem('dfg-adventure-audio')??'{}')};}catch{}
  prefs.effectsVolume=Math.max(0,Math.min(1,Number(prefs.effectsVolume)||0));
  prefs.volume=Math.max(0,Math.min(1,Number(prefs.volume)||0));
  let context,master,effectsMaster,active=null,wanted='courtyard',candidate=null,selectionTimer,token=0,unlocked=false,hidden=false,notice='';
  const buffers=new Map(),voices=new Set(),documents=new Set([document]),effectBuffers=new Map(),effectVoices=new Set(),lastEffects=new Map();
  let effectsPlayed=0;
  const save=()=>{try{localStorage.setItem('dfg-adventure-audio',JSON.stringify(prefs));}catch{}};
  function curve(a,b){const out=new Float32Array(128);for(let i=0;i<out.length;i++){const t=i/(out.length-1);out[i]=Math.sqrt((1-t)*a*a+t*b*b);}return out;}
  function ramp(param,value,duration){const now=context.currentTime;if(param.cancelAndHoldAtTime)param.cancelAndHoldAtTime(now);else{const current=param.value;param.cancelScheduledValues(now);param.setValueAtTime(current,now);}param.linearRampToValueAtTime(value,now+duration);}
  async function unlock(){
    if((!prefs.enabled&&!prefs.effectsEnabled)||!supported)return;
    try{if(!context){context=new(window.AudioContext||window.webkitAudioContext)();master=context.createGain();master.gain.value=hidden?0:prefs.volume;master.connect(context.destination);effectsMaster=context.createGain();effectsMaster.gain.value=hidden?0:prefs.effectsVolume;effectsMaster.connect(context.destination);context.addEventListener('statechange',()=>{unlocked=context.state==='running';});}await context.resume();unlocked=context.state==='running';if(unlocked){await playWanted();warmEffects();}}
    catch{notice='Tap Music to try again';paint();}
  }
  async function buffer(id){
    if(!buffers.has(id))buffers.set(id,(async()=>{const r=await fetch(new URL('adventure/audio/'+tracks[id][1],document.baseURI));if(!r.ok)throw Error('Music unavailable');return context.decodeAudioData(await r.arrayBuffer());})().catch(e=>{buffers.delete(id);throw e;}));
    return buffers.get(id);
  }
  function stopVoice(v,seconds=0){clearTimeout(v.timer);const when=context.currentTime+seconds;try{v.source.stop(when);}catch{}setTimeout(()=>{v.gain.disconnect();voices.delete(v);},Math.ceil(seconds*1000)+50);}
  function startVoice(id,b,when,fade=0){
    const source=context.createBufferSource(),gain=context.createGain();source.buffer=b;source.connect(gain);gain.connect(master);
    gain.gain.setValueAtTime(fade?0:1,when);if(fade)gain.gain.setValueCurveAtTime(curve(0,1),when,fade);
    const v={id,source,gain,timer:null,start:when};voices.add(v);source.start(when);
    // Overlap the recording's tail and head, rather than exposing the MP3 boundary.
    const overlap=Math.min(2,b.duration/8),next=when+b.duration-overlap;
    v.timer=setTimeout(()=>{
      if(active!==v||!prefs.enabled)return;
      const at=Math.max(next,context.currentTime+.03);const incoming=startVoice(id,b,at,overlap);active=incoming;
      gain.gain.setValueCurveAtTime(curve(1,0),at,overlap);stopVoice(v,at-context.currentTime+overlap+.03);
    },Math.max(0,(next-context.currentTime-.2)*1000));
    return v;
  }
  async function playWanted(){
    if(!unlocked||!prefs.enabled||active?.id===wanted)return;
    const request=++token,id=wanted;
    try{const b=await buffer(id);if(request!==token||!prefs.enabled||id!==wanted)return;
      const now=context.currentTime+.03,old=[...voices],fade=active?2.5:1.2;
      active=startVoice(id,b,now,fade);for(const v of old){clearTimeout(v.timer);ramp(v.gain.gain,0,fade);stopVoice(v,fade+.1);}notice='';paint();
    }catch{if(request===token){notice='Music unavailable · tap to retry';paint();}}
  }
  function select(id,{immediate=false}={}){
    if(!tracks[id])id='courtyard';if(candidate===id)return;candidate=id;clearTimeout(selectionTimer);
    const apply=()=>{wanted=id;paint();playWanted();};if(immediate)apply();else selectionTimer=setTimeout(apply,1600);
  }
  const effectPriority=id=>['victory','defeat'].includes(id)?5:id==='knockout'?4:['barrier-break','burst-break','break-stop','confirm-cash'].includes(id)?3:id.startsWith('hit-')||id==='block'?2:id.startsWith('burn-')?1:0;
  const effectNames=['card-swipe','card-return','commit','reveal','burn-bonus','burn-reaction','hit-light','hit-medium','hit-heavy','block','advantage-gain','advantage-spend','barrier-absorb','barrier-break','break-stop','burst-break','confirm-mark','confirm-cash','reaction-ready','round-ready','knockout','victory','defeat'];
  async function effectBuffer(id){if(!effectBuffers.has(id))effectBuffers.set(id,(async()=>{const r=await fetch(new URL('adventure/audio/effects/'+id+'-v1.mp3',document.baseURI));if(!r.ok)throw Error('Effect unavailable');return context.decodeAudioData(await r.arrayBuffer());})().catch(e=>{effectBuffers.delete(id);throw e;}));return effectBuffers.get(id);}
  function warmEffects(){if(!prefs.effectsEnabled)return;for(const id of effectNames)effectBuffer(id).catch(()=>{});}
  function stopEffects(){for(const v of effectVoices){try{v.source.stop();}catch{}v.gain.disconnect();}effectVoices.clear();}
  async function effect(id,{seat=null,delay=0}={}){
    if(!supported||!prefs.effectsEnabled||hidden||!effectNames.includes(id))return;
    if(!unlocked)await unlock();if(!unlocked)return;
    const now=context.currentTime,key=id+':'+seat,last=lastEffects.get(key)??-Infinity;if(now-last<.075)return;lastEffects.set(key,now);
    try{const b=await effectBuffer(id);if(!prefs.effectsEnabled||hidden||context.currentTime-now>delay+.4)return;
      const priority=effectPriority(id);
      if(priority>=4){for(const v of [...effectVoices])if(v.priority<2){try{v.source.stop();}catch{}effectVoices.delete(v);}}
      if(effectVoices.size>=6){const lowest=[...effectVoices].sort((a,b)=>a.priority-b.priority)[0];if(lowest.priority>=priority)return;try{lowest.source.stop();}catch{}effectVoices.delete(lowest);}
      const source=context.createBufferSource(),gain=context.createGain();source.buffer=b;source.playbackRate.value=priority<4?.97+Math.random()*.06:1;gain.gain.value=priority===0?.65:priority===5?.8:1;
      source.connect(gain);let pan;if(context.createStereoPanner){pan=context.createStereoPanner();pan.pan.value=seat===0?-.22:seat===1?.22:0;gain.connect(pan);pan.connect(effectsMaster);}else gain.connect(effectsMaster);
      const voice={source,gain,priority};effectVoices.add(voice);source.onended=()=>{gain.disconnect();pan?.disconnect();effectVoices.delete(voice);};source.start(Math.max(context.currentTime+.005,now+delay));effectsPlayed++;
    }catch{/* Optional asset failures never interfere with a card action. */}
  }
  function area(j){if(j.zone==='sanctuary')return 'sanctuary';const x=j.position.x*64+32,y=j.position.y*64+32;return x>1000?'temple':y>770&&x<430?'courtyard':x<500&&y<350?'grove':y<430&&x>540?'terrace':y>640?'canal':'market';}
  function toggle(){if(!supported)return;prefs.enabled=!prefs.enabled;notice='';save();if(prefs.enabled)unlock();else{++token;for(const v of voices){ramp(v.gain.gain,0,.25);stopVoice(v,.3);}active=null;}paint();}
  function paint(){for(const doc of documents)for(const root of doc.querySelectorAll('.dfg-audio-controls')){root.querySelector('[data-music-toggle]').disabled=!supported;root.querySelector('[data-music-toggle]').textContent=!supported?'♫ Music unavailable':prefs.enabled?'♫ Music on':'♫ Music off';root.querySelector('[data-music-toggle]').setAttribute('aria-pressed',String(prefs.enabled));root.querySelector('[data-effects-toggle]').disabled=!supported;root.querySelector('[data-effects-toggle]').textContent=prefs.effectsEnabled?'Effects on':'Effects off';root.querySelector('[data-effects-toggle]').setAttribute('aria-pressed',String(prefs.effectsEnabled));root.querySelector('output').textContent=!supported?'Audio playback is unavailable in this browser.':notice||(prefs.enabled?tracks[wanted][0]:'Enable the adventure soundtrack');}}
  function mount(doc=document){
    documents.add(doc);if(doc!==document)doc.defaultView.addEventListener('pagehide',()=>documents.delete(doc),{once:true});if(doc.querySelector('.dfg-audio-controls'))return;
    const root=doc.createElement('div');root.className='dfg-audio-controls';root.innerHTML='<button type="button" data-music-toggle aria-pressed="false">♫ Music off</button><details><summary aria-label="Sound settings">⚙</summary><div><label>Music volume<input aria-label="Music volume" type="range" min="0" max="100" step="1"></label><button type="button" data-effects-toggle aria-pressed="true">Effects on</button><label>Effects volume<input aria-label="Effects volume" type="range" min="0" max="100" step="1"></label><output aria-live="polite"></output><a href="adventure/soundtrack.php" target="_blank" rel="noopener">Sound library ↗</a></div></details>';
    doc.body.append(root);root.querySelector('[data-music-toggle]').addEventListener('click',toggle);
    const fxSlider=root.querySelector('[aria-label="Effects volume"]');fxSlider.value=String(Math.round(prefs.effectsVolume*100));fxSlider.addEventListener('input',()=>{prefs.effectsVolume=Number(fxSlider.value)/100;if(effectsMaster)ramp(effectsMaster.gain,hidden?0:prefs.effectsVolume,.05);save();});root.querySelector('[data-effects-toggle]').addEventListener('click',()=>{prefs.effectsEnabled=!prefs.effectsEnabled;if(prefs.effectsEnabled)unlock();else stopEffects();save();paint();});
    const slider=root.querySelector('[aria-label="Music volume"]');slider.value=String(Math.round(prefs.volume*100));slider.addEventListener('input',()=>{prefs.volume=Number(slider.value)/100;if(master)ramp(master.gain,hidden?0:prefs.volume,.1);save();});paint();
  }
  document.addEventListener('pointerdown',()=>{if((prefs.enabled||prefs.effectsEnabled)&&!unlocked)unlock();},{capture:true});document.addEventListener('keydown',()=>{if((prefs.enabled||prefs.effectsEnabled)&&!unlocked)unlock();},{capture:true});
  document.addEventListener('visibilitychange',()=>{hidden=document.hidden;if(master)ramp(master.gain,hidden?0:prefs.volume,.4);if(effectsMaster)ramp(effectsMaster.gain,hidden?0:prefs.effectsVolume,.1);if(hidden)stopEffects();});
  addEventListener('pagehide',()=>{if(context)context.close().catch(()=>{});});
  return {mount,select,area,unlock,effect,get status(){return {supported,wanted,playing:active?.id??null,enabled:prefs.enabled,unlocked,voices:voices.size,effectsEnabled:prefs.effectsEnabled,effectsPlayed,effectVoices:effectVoices.size,contextState:context?.state??'locked'};}};
})();
window.DFGSoundtrack.mount(document);
