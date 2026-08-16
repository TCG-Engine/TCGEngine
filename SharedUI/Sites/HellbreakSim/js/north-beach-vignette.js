(function(){
  try{
    if(window.localStorage.getItem('northBeachIntroSeen:v1')!=='1'){
      document.documentElement.classList.add('nb-first-visit-pending');
      window.setTimeout(function(){document.documentElement.classList.remove('nb-first-visit-pending');},4000);
    }
  }catch(error){}
})();
(function(){
  'use strict';
  var root,scroller,scenes,observer,soundButton,previousFocus;
  var current=0,impact=0,attacking=false,wheelEnergy=0,lastBumpAt=0,bumpTimer=null,openingReset=false;
  var audio=null,masterGain=null,ambienceGain=null,effectsGain=null,musicGain=null,compressor=null,noiseBuffer=null;
  var ambienceNodes=[],soundTimers=[],ambienceTimer=null,ambienceCount=0,soundScene=-1,soundPreference=false;
  var introSeenKey='northBeachIntroSeen:v1';
  var sampleBase='/TCGEngine/SharedUI/Sites/HellbreakSim/assets/audio/';
  var sampleUrls={
    shoreline:'north-beach-sunny-shoreline.mp3',welcomeChime:'north-beach-welcome-chime.mp3',cardFan:'north-beach-card-fan.mp3',cardDeal:'north-beach-card-deal.mp3',rewardChime:'north-beach-reward-chime.mp3',
    shallows:'north-beach-shallows-water.mp3',openWater:'north-beach-open-water.mp3',buoyBell:'north-beach-buoy-bell.mp3',submergedPass:'north-beach-submerged-pass.mp3',woodRattle:'north-beach-wooden-hull-rattle.mp3',
    boatArrival:'north-beach-boat-arrival.mp3',hullCreak:'north-beach-hull-creak.mp3',boatAmbience:'north-beach-boat-ambience.mp3',hullThud:'north-beach-dry-hull-thud.mp3',cardRattle:'north-beach-card-rattle.mp3',
    sharkAttack:'north-beach-shark-attack.mp3',swallowed:'north-beach-swallowed-transition.mp3',underwaterMenu:'north-beach-underwater-menu.mp3',finalMusic:'north-beach-final-music.mp3',underwaterSharkPass:'north-beach-underwater-shark-pass.mp3',finalSting:'north-beach-final-sting.mp3'
  };
  var sampleBuffers={},samplePromises={},sampleSources=[];
  var impactMessages=[
    ['Keep scrolling','You\u2019re almost there'],
    ['Keep scrolling','Next: deep water'],
    ['Keep scrolling','The tide is changing'],
    ['Keep scrolling','That came from below'],
    ['Keep scrolling','Don\u2019t look down']
  ];

  function audioContext(){
    if(audio)return audio;
    var AudioCtx=window.AudioContext||window.webkitAudioContext;
    if(!AudioCtx)return null;
    audio=new AudioCtx();
    masterGain=audio.createGain();ambienceGain=audio.createGain();effectsGain=audio.createGain();musicGain=audio.createGain();compressor=audio.createDynamicsCompressor();
    masterGain.gain.value=.0001;ambienceGain.gain.value=.82;effectsGain.gain.value=.9;musicGain.gain.value=.72;
    compressor.threshold.value=-18;compressor.knee.value=12;compressor.ratio.value=4;compressor.attack.value=.004;compressor.release.value=.28;
    ambienceGain.connect(masterGain);effectsGain.connect(masterGain);musicGain.connect(masterGain);masterGain.connect(compressor);compressor.connect(audio.destination);
    root.setAttribute('data-audio-state',audio.state);
    return audio;
  }
  function soundOn(){return !!root&&root.getAttribute('data-sound')==='on'&&root.classList.contains('is-open');}
  function ramp(param,value,duration){
    if(!audio||!param)return;
    var now=audio.currentTime,currentValue=Math.max(.0001,param.value||.0001);
    param.cancelScheduledValues(now);param.setValueAtTime(currentValue,now);param.exponentialRampToValueAtTime(Math.max(.0001,value),now+Math.max(.01,duration));
  }
  function panNode(value){
    if(!audio||!audio.createStereoPanner)return null;
    var pan=audio.createStereoPanner();pan.pan.value=Math.max(-1,Math.min(1,value||0));return pan;
  }
  function connectVoice(source,gain,bus,pan){
    var p=panNode(pan);source.connect(gain);if(p){gain.connect(p);p.connect(bus);}else gain.connect(bus);
    return p;
  }
  function loadSample(name){
    var ctx=audioContext();if(!ctx||!sampleUrls[name])return Promise.reject(new Error('Audio sample unavailable'));
    if(sampleBuffers[name])return Promise.resolve(sampleBuffers[name]);
    if(samplePromises[name])return samplePromises[name];
    samplePromises[name]=window.fetch(sampleBase+sampleUrls[name],{credentials:'same-origin'}).then(function(response){if(!response.ok)throw new Error('Audio sample failed to load');return response.arrayBuffer();}).then(function(data){return ctx.decodeAudioData(data);}).then(function(buffer){sampleBuffers[name]=buffer;return buffer;}).catch(function(error){delete samplePromises[name];throw error;});
    return samplePromises[name];
  }
  function preloadSamples(){Object.keys(sampleUrls).forEach(function(name){loadSample(name).catch(function(){});});}
  function playSample(name,bus,volume,pan,loop,playbackRate,delay,fadeOutAt,stopAt,valid){
    var requestedScene=current;
    return loadSample(name).then(function(buffer){
      if(!soundOn()||(valid&&!valid(requestedScene,current)))return null;
      var ctx=audioContext(),when=ctx.currentTime+(delay||0),source=ctx.createBufferSource(),gain=ctx.createGain(),panStart=Array.isArray(pan)?pan[0]:(pan||0);
      source.buffer=buffer;source.loop=!!loop;source.playbackRate.value=playbackRate||1;gain.gain.value=Math.max(.0001,volume||.3);var panner=connectVoice(source,gain,bus||effectsGain,panStart);
      if(Array.isArray(pan)&&panner){var panDuration=stopAt||buffer.duration/(playbackRate||1);panner.pan.setValueAtTime(panStart,when);panner.pan.linearRampToValueAtTime(pan[1],when+panDuration);}
      if(fadeOutAt!=null){gain.gain.setValueAtTime(Math.max(.0001,volume||.3),when+fadeOutAt);gain.gain.exponentialRampToValueAtTime(.0001,when+(stopAt||fadeOutAt+.5));}
      source.start(when);if(stopAt!=null)source.stop(when+stopAt+.04);if(loop)ambienceNodes.push({source:source,gain:gain});else{var sampleNode={source:source,gain:gain};sampleSources.push(sampleNode);source.onended=function(){sampleSources=sampleSources.filter(function(node){return node!==sampleNode;});};}
      return source;
    }).catch(function(){return null;});
  }
  function toneSweep(from,to,duration,volume,type,delay,pan,bus){
    var ctx=audioContext();if(!ctx||!soundOn())return;
    var when=ctx.currentTime+(delay||0),osc=ctx.createOscillator(),gain=ctx.createGain();
    osc.type=type||'sine';osc.frequency.setValueAtTime(Math.max(1,from),when);osc.frequency.exponentialRampToValueAtTime(Math.max(1,to||from),when+duration);
    gain.gain.setValueAtTime(.0001,when);gain.gain.exponentialRampToValueAtTime(Math.max(.0001,volume||.02),when+Math.min(.035,duration*.15));gain.gain.exponentialRampToValueAtTime(.0001,when+duration);
    connectVoice(osc,gain,bus||effectsGain,pan);osc.start(when);osc.stop(when+duration+.03);
  }
  function getNoiseBuffer(){
    if(noiseBuffer)return noiseBuffer;
    var ctx=audioContext(),length=ctx.sampleRate*2;noiseBuffer=ctx.createBuffer(1,length,ctx.sampleRate);var data=noiseBuffer.getChannelData(0);
    for(var i=0;i<length;i++)data[i]=Math.random()*2-1;
    return noiseBuffer;
  }
  function noiseBurst(duration,volume,filterType,frequency,delay,pan,bus){
    var ctx=audioContext();if(!ctx||!soundOn())return;
    var when=ctx.currentTime+(delay||0),source=ctx.createBufferSource(),filter=ctx.createBiquadFilter(),gain=ctx.createGain();
    source.buffer=getNoiseBuffer();filter.type=filterType||'lowpass';filter.frequency.value=frequency||700;filter.Q.value=.7;
    gain.gain.setValueAtTime(.0001,when);gain.gain.exponentialRampToValueAtTime(Math.max(.0001,volume||.02),when+Math.min(.025,duration*.18));gain.gain.exponentialRampToValueAtTime(.0001,when+duration);
    source.connect(filter);connectVoice(filter,gain,bus||effectsGain,pan);source.start(when);source.stop(when+duration+.03);
  }
  function movingNoise(duration,volume,startPan,endPan,delay){
    var ctx=audioContext();if(!ctx||!soundOn())return;
    var when=ctx.currentTime+(delay||0),source=ctx.createBufferSource(),filter=ctx.createBiquadFilter(),gain=ctx.createGain(),pan=panNode(startPan);
    source.buffer=getNoiseBuffer();source.loop=true;filter.type='lowpass';filter.frequency.value=190;gain.gain.setValueAtTime(.0001,when);gain.gain.exponentialRampToValueAtTime(volume,when+1);gain.gain.setValueAtTime(volume,when+duration-1);gain.gain.exponentialRampToValueAtTime(.0001,when+duration);
    source.connect(filter);filter.connect(gain);if(pan){gain.connect(pan);pan.connect(ambienceGain);pan.pan.setValueAtTime(startPan,when);pan.pan.linearRampToValueAtTime(endPan,when+duration);}else gain.connect(ambienceGain);
    source.start(when);source.stop(when+duration+.05);
  }
  function startNoiseBed(volume,frequency,rate){
    var ctx=audioContext(),source=ctx.createBufferSource(),filter=ctx.createBiquadFilter(),gain=ctx.createGain(),lfo=ctx.createOscillator(),depth=ctx.createGain();
    source.buffer=getNoiseBuffer();source.loop=true;filter.type='lowpass';filter.frequency.value=frequency;filter.Q.value=.55;gain.gain.value=volume;
    lfo.type='sine';lfo.frequency.value=rate||.1;depth.gain.value=volume*.42;lfo.connect(depth);depth.connect(gain.gain);source.connect(filter);filter.connect(gain);gain.connect(ambienceGain);source.start();lfo.start();
    ambienceNodes.push({source:source,lfo:lfo,gain:gain});
  }
  function startToneBed(frequency,volume,type){
    var ctx=audioContext(),osc=ctx.createOscillator(),gain=ctx.createGain();osc.type=type||'sine';osc.frequency.value=frequency;gain.gain.value=volume;osc.connect(gain);gain.connect(ambienceGain);osc.start();ambienceNodes.push({source:osc,gain:gain});
  }
  function clearSoundTimers(){
    if(ambienceTimer){window.clearInterval(ambienceTimer);ambienceTimer=null;}
    soundTimers.forEach(function(timer){window.clearTimeout(timer);});soundTimers=[];
  }
  function later(callback,delay){var timer=window.setTimeout(callback,delay);soundTimers.push(timer);return timer;}
  function stopBeds(duration){
    if(!audio){ambienceNodes=[];return;}
    var now=audio.currentTime,fade=duration==null?.35:duration;
    ambienceNodes.forEach(function(node){try{node.gain.gain.cancelScheduledValues(now);node.gain.gain.setValueAtTime(Math.max(.0001,node.gain.gain.value),now);node.gain.gain.exponentialRampToValueAtTime(.0001,now+fade);node.source.stop(now+fade+.04);if(node.lfo)node.lfo.stop(now+fade+.04);}catch(error){}});ambienceNodes=[];
  }
  function stopSampleSources(duration){
    if(!audio){sampleSources=[];return;}
    var now=audio.currentTime,fade=duration==null?.12:duration,nodes=sampleSources.slice();sampleSources=[];
    nodes.forEach(function(node){try{node.gain.gain.cancelScheduledValues(now);node.gain.gain.setValueAtTime(Math.max(.0001,node.gain.gain.value),now);node.gain.gain.exponentialRampToValueAtTime(.0001,now+fade);node.source.stop(now+fade+.04);}catch(error){}});
  }
  function stopAmbience(){clearSoundTimers();stopBeds(.32);}

  function musicalSting(notes,volume,delay){notes.forEach(function(note,index){toneSweep(note,note*1.006,1.4,volume,'sine',(delay||0)+index*.055,(index-1)*.18,musicGain);});}
  function cardFan(){playSample('cardFan',effectsGain,.62,[-.3,.3],false,1,.3,null,null,function(requested,active){return requested===1&&active===1;});}
  function cardDeal(){playSample('cardDeal',effectsGain,.64,[-.28,.28],false,1,0,null,null,function(requested,active){return requested===2&&active===2;});}
  function gullCall(delay){toneSweep(720,1180,.34,.009,'sine',delay||0,-.72,ambienceGain);toneSweep(1180,690,.48,.007,'sine',(delay||0)+.3,-.65,ambienceGain);}
  function buoyBell(delay){playSample('buoyBell',effectsGain,.42,-.76,false,1,delay||0,null,null,function(requested,active){return requested===4&&active===4;});}
  function submergedPass(delay,startPan,endPan){playSample('submergedPass',effectsGain,.38,[startPan==null?-.72:startPan,endPan==null?.72:endPan],false,1,delay||0,4.9,5.8,function(requested,active){return requested>=4&&requested<=5&&active>=4&&active<=5;});}
  function woodCreak(volume,delay,pan){playSample('hullCreak',effectsGain,volume||.32,pan||0,false,1,delay||0,null,null,function(requested,active){return requested>=5&&requested<=6&&active>=5&&active<=6;});}
  function bumpSound(stage){
    var pan=stage%2?-.2:.2;
    playSample('hullThud',effectsGain,.3+stage*.11,pan,false,1.07-stage*.025,0,null,null,function(requested,active){return requested===6&&active===6;});
    if(stage>=2)playSample('cardRattle',effectsGain,.18+stage*.08,[-pan,pan],false,1.04-stage*.018,.045,1.85,2.35,function(requested,active){return requested===6&&active===6;});
    if(stage>=3)woodCreak(.2+stage*.045,.12,-pan);
    toneSweep(Math.max(30,57-stage*4),Math.max(24,43-stage*3),.28+stage*.055,.012+stage*.008,'sine',0,pan,effectsGain);
  }
  function attackSound(){
    clearSoundTimers();stopBeds(.72);stopSampleSources(.12);
    playSample('sharkAttack',effectsGain,.9,0,false,1,0,1.82,2.45,function(requested){return requested===6;});
    playSample('swallowed',effectsGain,.64,0,false,1,.72,1.18,1.8,function(requested){return requested===6;});
    toneSweep(63,25,1.45,.075,'sine',0,0,effectsGain);
    if(masterGain&&audio){var now=audio.currentTime;masterGain.gain.cancelScheduledValues(now+1.72);masterGain.gain.setValueAtTime(Math.max(.0001,masterGain.gain.value),now+1.72);masterGain.gain.exponentialRampToValueAtTime(.045,now+2.24);}
  }
  function finalSharkPasses(){
    function cycle(){if(current!==7||!soundOn())return;playSample('underwaterSharkPass',ambienceGain,.28,[-.9,.9],false,1,1.1,5.9,6.8,function(requested,active){return requested===7&&active===7;});playSample('underwaterSharkPass',ambienceGain,.25,[.9,-.9],false,.98,13.1,6,6.9,function(requested,active){return requested===7&&active===7;});later(cycle,24000);}
    cycle();
  }
  function sceneCue(index){
    if(index===0)playSample('welcomeChime',musicGain,.54,0,false,1,.08,null,null,function(requested,active){return requested===0&&active===0;});
    else if(index===1)cardFan();
    else if(index===2){cardDeal();playSample('rewardChime',musicGain,.46,.16,false,1,.72,null,null,function(requested,active){return requested===2&&active===2;});}
    else if(index===3)playSample('rewardChime',musicGain,.4,.22,false,1,.55,null,null,function(requested,active){return requested===3&&active===3;});
    else if(index===4){submergedPass(.35,-.72,.72);buoyBell(.4);}
    else if(index===6)playSample('boatArrival',effectsGain,.58,[-.16,.1],false,1,.08,2.75,3.15,function(requested,active){return requested===6&&active===6;});
    else if(index===7){ramp(masterGain.gain,.72,1.05);playSample('finalSting',musicGain,.42,0,false,1,.3,3.1,3.9,function(requested,active){return requested===7&&active===7;});finalSharkPasses();}
  }
  function ambienceTick(){
    if(!soundOn())return;ambienceCount++;
    if(soundScene===4){buoyBell();if(ambienceCount%5===3)submergedPass(.1,ambienceCount%2?-.72:.72,ambienceCount%2?.72:-.72);}
    else if(soundScene===6&&ambienceCount%3===0)woodCreak(.22,0,ambienceCount%2?-.42:.42);
  }
  function startAmbience(index){
    clearSoundTimers();stopBeds(.28);if(!soundOn())return;audioContext().resume();ambienceCount=0;
    if(index<=2)playSample('shoreline',ambienceGain,.42,0,true,1,0,null,null,function(requested,active){return requested<=2&&active<=2;}).then(function(source){if(!source&&soundOn()&&current<=2)startNoiseBed(.027+(current*.004),880-current*90,.085);});
    else if(index===3)playSample('shallows',ambienceGain,.46,0,true,1,0,null,null,function(requested,active){return requested===3&&active===3;}).then(function(source){if(!source&&soundOn()&&current===3)startNoiseBed(.04,590,.075);});
    else if(index===4)playSample('openWater',ambienceGain,.43,0,true,1,0,null,null,function(requested,active){return requested===4&&active===4;}).then(function(source){if(!source&&soundOn()&&current===4)startNoiseBed(.038,310,.055);});
    else if(index===5){playSample('openWater',ambienceGain,.9,0,true,1,0,null,null,function(requested,active){return requested===5&&active===5;}).then(function(source){if(!source&&soundOn()&&current===5)startNoiseBed(.04,310,.055);});playSample('woodRattle',ambienceGain,2.2,0,true,1,0,null,null,function(requested,active){return requested===5&&active===5;});}
    else if(index===6){playSample('boatAmbience',ambienceGain,.38,0,true,1,0,null,null,function(requested,active){return requested===6&&active===6;}).then(function(source){if(!source&&soundOn()&&current===6)startNoiseBed(.029,520,.12);});playSample('woodRattle',ambienceGain,.46,-.08,true,1,0,null,null,function(requested,active){return requested===6&&active===6;});startToneBed(31,.006,'sine');}
    else if(index===7){playSample('underwaterMenu',ambienceGain,.42,0,true,1,0,null,null,function(requested,active){return requested===7&&active===7;}).then(function(source){if(!source&&soundOn()&&current===7)startNoiseBed(.041,235,.045);});playSample('finalMusic',musicGain,.3,0,true,1,0,null,null,function(requested,active){return requested===7&&active===7;});startToneBed(29,.005,'sine');}
    sceneCue(index);ambienceTimer=window.setInterval(ambienceTick,index===4?2000:(index===6?3500:4000));
  }
  function setSoundScene(index,force){
    if(!force&&soundScene===index)return;
    var keepSunnyBed=!force&&soundScene>=0&&soundScene<=2&&index<=2;soundScene=index;
    if(keepSunnyBed){clearSoundTimers();ambienceCount=0;sceneCue(index);ambienceTimer=window.setInterval(ambienceTick,2700);return;}
    startAmbience(index);
  }
  function updateSoundButton(){
    if(!soundButton)return;var on=root.getAttribute('data-sound')==='on';soundButton.setAttribute('aria-pressed',on?'true':'false');soundButton.setAttribute('aria-label',on?'Turn sound off':'Turn sound on');var icon=soundButton.querySelector('span');if(icon)icon.textContent=on?'♫':'♩';
  }
  function setSoundEnabled(on,persist){
    if(!on&&soundOn()&&audio)toneSweep(520,300,.18,.016,'sine',0,0,musicGain);
    soundPreference=!!on;root.setAttribute('data-sound',on?'on':'off');updateSoundButton();
    if(persist!==false){try{window.localStorage.setItem('northBeachSound',on?'on':'off');}catch(error){}}
    if(on){var ctx=audioContext();if(!ctx)return;preloadSamples();ctx.resume().then(function(){root.setAttribute('data-audio-state',ctx.state);ramp(masterGain.gain,.72,.38);setSoundScene(current,true);toneSweep(440,660,.24,.022,'sine',0,0,musicGain);}).catch(function(){root.setAttribute('data-audio-state',ctx.state);});}
    else{if(audio)ramp(masterGain.gain,.0001,.22);stopAmbience();stopSampleSources(.12);}
  }
  function resumeSoundFromGesture(){
    if(!soundPreference||!soundOn())return;var ctx=audioContext();if(!ctx||ctx.state==='running')return;
    ctx.resume().then(function(){if(!soundOn())return;root.setAttribute('data-audio-state',ctx.state);ramp(masterGain.gain,.72,.24);setSoundScene(current,true);}).catch(function(){root.setAttribute('data-audio-state',ctx.state);});
  }
  function pauseSoundscape(){stopAmbience();stopSampleSources(.12);if(audio)ramp(masterGain.gain,.0001,.18);}

  function updateImpactMessage(stage){
    var message=impactMessages[Math.min(stage,impactMessages.length-1)],command=root.querySelector('[data-nb-impact-command]'),status=root.querySelector('[data-nb-impact-status]');
    if(command)command.textContent=message[0];if(status)status.textContent=message[1];
  }
  function updateScene(index){
    var changed=current!==index;current=index;root.classList.toggle('is-darkening',index>=4);root.classList.toggle('is-impact-scene',index===6);
    root.querySelectorAll('.nb-progress span').forEach(function(dot,i){dot.classList.toggle('is-active',i<=Math.min(index,5));});
    if(changed)setSoundScene(index,false);
  }
  function rememberIntroVisit(){try{window.localStorage.setItem(introSeenKey,'1');}catch(error){}}
  function isFirstVisit(){try{return window.localStorage.getItem(introSeenKey)!=='1';}catch(error){return false;}}
  function observeScenes(){if(!observer)return;scenes.forEach(function(scene){observer.observe(scene);});}
  function resetTour(){
    if(observer){observer.disconnect();observer.takeRecords();}
    if(bumpTimer){window.clearTimeout(bumpTimer);bumpTimer=null;}
    root.classList.remove('is-open','is-attacking','is-darkening','is-bumping','is-impact-scene','is-swallowed','is-revealing');root.removeAttribute('data-impact');
    scenes.forEach(function(scene){scene.classList.remove('is-visible');});
    impact=0;wheelEnergy=0;lastBumpAt=0;attacking=false;current=0;soundScene=-1;scroller.style.overflowY='auto';scroller.scrollTop=0;updateImpactMessage(0);updateScene(0);void scroller.offsetHeight;
  }
  function open(){
    openingReset=true;previousFocus=document.activeElement;pauseSoundscape();resetTour();rememberIntroVisit();
    document.body.classList.add('nb-open');root.classList.add('is-open');root.setAttribute('aria-hidden','false');scroller.scrollTop=0;scenes[0].classList.add('is-visible');observeScenes();document.documentElement.classList.remove('nb-first-visit-pending');
    if(soundPreference){root.setAttribute('data-sound','off');updateSoundButton();}scroller.focus({preventScroll:true});if(soundPreference)setSoundEnabled(true,false);
    window.requestAnimationFrame(function(){scroller.scrollTop=0;window.requestAnimationFrame(function(){scroller.scrollTop=0;if(observer)observer.takeRecords();openingReset=false;});});
  }
  function close(){
    pauseSoundscape();root.classList.remove('is-open','is-attacking','is-darkening','is-bumping','is-impact-scene','is-swallowed','is-revealing');root.setAttribute('aria-hidden','true');document.body.classList.remove('nb-open');
    if(previousFocus&&previousFocus.focus)previousFocus.focus();
  }
  function go(index){if(scenes[index])scenes[index].scrollIntoView({behavior:'smooth'});}
  function strike(){
    if(attacking)return;attacking=true;if(bumpTimer)window.clearTimeout(bumpTimer);root.classList.remove('is-bumping');root.classList.add('is-attacking');attackSound();
    window.setTimeout(function(){root.classList.add('is-swallowed');},1900);
    window.setTimeout(function(){root.classList.add('is-revealing');scroller.style.overflowY='auto';go(7);},2250);
  }
  function bump(){
    if(attacking)return;impact++;
    if(impact<5){root.setAttribute('data-impact',String(impact));updateImpactMessage(impact);root.classList.remove('is-bumping');void root.offsetWidth;root.classList.add('is-bumping');if(bumpTimer)window.clearTimeout(bumpTimer);bumpTimer=window.setTimeout(function(){root.classList.remove('is-bumping');},impact>=4?820:(impact===3?580:430));bumpSound(impact);}else strike();
  }
  function handleFinalWheel(event){
    if(current!==6||attacking)return;event.preventDefault();wheelEnergy+=Math.abs(event.deltaY||0);var now=Date.now();if(wheelEnergy>=80&&now-lastBumpAt>=420){wheelEnergy=0;lastBumpAt=now;bump();}
  }
  function action(name){
    close();if(name==='play'){var btn=document.getElementById('join-queue-btn');if(btn)btn.focus();return;}if(window.Toast)window.Toast(name.charAt(0).toUpperCase()+name.slice(1)+' is coming soon.',{type:'info'});
  }
  function init(){
    root=document.getElementById('north-beach-vignette');if(!root)return;scroller=root.querySelector('.nb-scroll');scenes=Array.from(root.querySelectorAll('[data-nb-scene]'));soundButton=root.querySelector('[data-nb-sound]');
    try{soundPreference=window.localStorage.getItem('northBeachSound')==='on';}catch(error){soundPreference=false;}root.setAttribute('data-sound',soundPreference?'on':'off');updateSoundButton();
    observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting&&entry.intersectionRatio>.55){var i=Number(entry.target.dataset.nbScene);if(openingReset&&i!==0)return;entry.target.classList.add('is-visible');updateScene(i);if(i===6&&!attacking){scroller.scrollTop=entry.target.offsetTop;wheelEnergy=0;}}});},{root:scroller,threshold:[.55]});
    observeScenes();document.querySelectorAll('[data-nb-open]').forEach(function(button){button.addEventListener('click',open);});root.querySelectorAll('[data-nb-close]').forEach(function(button){button.addEventListener('click',close);});
    root.querySelector('[data-nb-home]').addEventListener('click',function(){scroller.style.overflowY='auto';go(0);});root.querySelector('[data-nb-next]').addEventListener('click',function(){go(1);});
    soundButton.addEventListener('click',function(){setSoundEnabled(root.getAttribute('data-sound')!=='on',true);});root.querySelectorAll('[data-nb-action]').forEach(function(button){button.addEventListener('click',function(){action(this.dataset.nbAction);});});
    root.addEventListener('wheel',handleFinalWheel,{passive:false,capture:true});root.addEventListener('touchstart',function(event){scroller.dataset.touchY=String(event.touches[0].clientY);},{passive:true});
    root.addEventListener('pointerdown',resumeSoundFromGesture,{capture:true});root.addEventListener('wheel',resumeSoundFromGesture,{passive:true,capture:true});root.addEventListener('touchstart',resumeSoundFromGesture,{passive:true,capture:true});root.addEventListener('keydown',resumeSoundFromGesture,{capture:true});
    root.addEventListener('touchmove',function(event){if(current===6){var y=Number(scroller.dataset.touchY||event.touches[0].clientY);if(y-event.touches[0].clientY>28&&Date.now()-lastBumpAt>=420){event.preventDefault();scroller.dataset.touchY=String(event.touches[0].clientY);lastBumpAt=Date.now();bump();}}},{passive:false});
    root.addEventListener('keydown',function(event){if(event.key==='Escape'){close();return;}if(current===6&&(event.key==='ArrowDown'||event.key==='PageDown'||event.key===' ')){event.preventDefault();bump();}});
    document.addEventListener('visibilitychange',function(){if(document.hidden)pauseSoundscape();else if(root.classList.contains('is-open')&&soundPreference)setSoundEnabled(true,false);});
    if(isFirstVisit())open();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
