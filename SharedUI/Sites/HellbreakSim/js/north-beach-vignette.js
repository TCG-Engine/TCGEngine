(function(){
  'use strict';
  var root,scroller,scenes,observer,current=0,impact=0,attacking=false,audio=null,noiseTimer=null,previousFocus=null,wheelEnergy=0,lastBumpAt=0,bumpTimer=null;
  var impactMessages=[
    ['Keep scrolling','You\u2019re almost there'],
    ['Keep scrolling','Next: deep water'],
    ['Keep scrolling','The tide is changing'],
    ['Keep scrolling','That came from below'],
    ['Keep scrolling','Don\u2019t look down']
  ];

  function audioContext(){
    if(audio) return audio;
    var AudioCtx=window.AudioContext||window.webkitAudioContext;
    if(!AudioCtx) return null;
    audio=new AudioCtx();
    return audio;
  }
  function tone(frequency,duration,volume,type){
    var ctx=audioContext(); if(!ctx||root.getAttribute('data-sound')!=='on') return;
    var osc=ctx.createOscillator(),gain=ctx.createGain(); osc.type=type||'sine';osc.frequency.value=frequency;
    gain.gain.setValueAtTime(volume||.025,ctx.currentTime);gain.gain.exponentialRampToValueAtTime(.0001,ctx.currentTime+duration);
    osc.connect(gain);gain.connect(ctx.destination);osc.start();osc.stop(ctx.currentTime+duration);
  }
  function ambience(){
    stopAmbience(); if(root.getAttribute('data-sound')!=='on') return;
    tone(196,.7,.012,'sine');
    noiseTimer=window.setInterval(function(){ if(current<4) tone(170+Math.random()*80,1.8,.007,'sine'); },3200);
  }
  function stopAmbience(){ if(noiseTimer){window.clearInterval(noiseTimer);noiseTimer=null;} }
  function updateImpactMessage(stage){
    var message=impactMessages[Math.min(stage,impactMessages.length-1)];
    var command=root.querySelector('[data-nb-impact-command]'),status=root.querySelector('[data-nb-impact-status]');
    if(command)command.textContent=message[0];
    if(status)status.textContent=message[1];
  }
  function updateScene(index){
    current=index;
    root.classList.toggle('is-darkening',index>=4);
    root.classList.toggle('is-impact-scene',index===6);
    root.querySelectorAll('.nb-progress span').forEach(function(dot,i){dot.classList.toggle('is-active',i<=Math.min(index,5));});
    if(index>=4) stopAmbience();
  }
  function open(){
    previousFocus=document.activeElement; document.body.classList.add('nb-open');root.classList.add('is-open');root.setAttribute('aria-hidden','false');
    root.classList.remove('is-attacking','is-darkening','is-bumping','is-impact-scene','is-revealing');root.removeAttribute('data-impact');impact=0;wheelEnergy=0;attacking=false;scroller.style.overflowY='auto';updateImpactMessage(0);
    scroller.scrollTop=0;scroller.focus({preventScroll:true});updateScene(0);
  }
  function close(){
    stopAmbience();root.classList.remove('is-open','is-attacking','is-darkening','is-bumping','is-impact-scene','is-revealing');root.setAttribute('aria-hidden','true');document.body.classList.remove('nb-open');
    if(previousFocus&&previousFocus.focus) previousFocus.focus();
  }
  function go(index){ if(scenes[index])scenes[index].scrollIntoView({behavior:'smooth'}); }
  function strike(){
    if(attacking)return;attacking=true;if(bumpTimer)window.clearTimeout(bumpTimer);root.classList.remove('is-bumping');root.classList.add('is-attacking');tone(42,1.1,.09,'sawtooth');tone(82,.5,.045,'square');
    window.setTimeout(function(){root.classList.add('is-revealing');scroller.style.overflowY='auto';go(7);},1700);
  }
  function bump(){
    if(attacking)return;impact++;
    if(impact<5){
      root.setAttribute('data-impact',String(impact));
      updateImpactMessage(impact);
      root.classList.remove('is-bumping');void root.offsetWidth;root.classList.add('is-bumping');
      if(bumpTimer)window.clearTimeout(bumpTimer);bumpTimer=window.setTimeout(function(){root.classList.remove('is-bumping');},impact>=4?820:(impact===3?580:430));
      tone(Math.max(30,52-impact*4),.32+impact*.08,.035+impact*.012,'sine');
    } else strike();
  }
  function handleFinalWheel(event){
    if(current!==6||attacking)return;
    event.preventDefault();
    wheelEnergy+=Math.abs(event.deltaY||0);
    var now=Date.now();
    if(wheelEnergy>=80&&now-lastBumpAt>=420){wheelEnergy=0;lastBumpAt=now;bump();}
  }
  function action(name){
    close();
    if(name==='play'){var btn=document.getElementById('join-queue-btn');if(btn)btn.focus();return;}
    if(window.Toast)window.Toast(name.charAt(0).toUpperCase()+name.slice(1)+' is coming soon.',{type:'info'});
  }
  function init(){
    root=document.getElementById('north-beach-vignette');if(!root)return;scroller=root.querySelector('.nb-scroll');scenes=Array.from(root.querySelectorAll('[data-nb-scene]'));
    observer=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting&&entry.intersectionRatio>.55){var i=Number(entry.target.dataset.nbScene);entry.target.classList.add('is-visible');updateScene(i);if(i===6&&!attacking){scroller.scrollTop=entry.target.offsetTop;wheelEnergy=0;}}});},{root:scroller,threshold:[.55]});
    scenes.forEach(function(scene){observer.observe(scene);});
    document.querySelectorAll('[data-nb-open]').forEach(function(button){button.addEventListener('click',open);});
    root.querySelectorAll('[data-nb-close]').forEach(function(button){button.addEventListener('click',close);});
    root.querySelector('[data-nb-home]').addEventListener('click',function(){scroller.style.overflowY='auto';go(0);});
    root.querySelector('[data-nb-next]').addEventListener('click',function(){go(1);});
    root.querySelector('[data-nb-sound]').addEventListener('click',function(){var on=root.getAttribute('data-sound')!=='on';root.setAttribute('data-sound',on?'on':'off');this.setAttribute('aria-pressed',on?'true':'false');this.querySelector('span').textContent=on?'♫':'♩';if(on)ambience();else stopAmbience();});
    root.querySelectorAll('[data-nb-action]').forEach(function(button){button.addEventListener('click',function(){action(this.dataset.nbAction);});});
    root.addEventListener('wheel',handleFinalWheel,{passive:false,capture:true});
    root.addEventListener('touchstart',function(event){scroller.dataset.touchY=String(event.touches[0].clientY);},{passive:true});
    root.addEventListener('touchmove',function(event){if(current===6){var y=Number(scroller.dataset.touchY||event.touches[0].clientY);if(y-event.touches[0].clientY>28&&Date.now()-lastBumpAt>=420){event.preventDefault();scroller.dataset.touchY=String(event.touches[0].clientY);lastBumpAt=Date.now();bump();}}},{passive:false});
    root.addEventListener('keydown',function(event){if(event.key==='Escape'){close();return;}if(current===6&&(event.key==='ArrowDown'||event.key==='PageDown'||event.key===' ')){event.preventDefault();bump();}});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
