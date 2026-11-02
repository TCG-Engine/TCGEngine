'use strict';
(async()=>{
  const videos=[...document.querySelectorAll('video[data-cedar-strength]')];
  const canvas=document.getElementById('cedarStrengthMap'),ctx=canvas.getContext('2d');
  const select=document.getElementById('cedarStrengthChoice'),status=document.getElementById('cedarStrengthStatus');
  let original=false;
  const sync=()=>{
    for(const button of document.querySelectorAll('[data-cedar-toggle]')){
      const v=videos.find(v=>v.dataset.cedarStrength===button.dataset.cedarToggle);
      button.textContent=(v.paused?'Play ':'Pause ')+button.dataset.label;
      button.setAttribute('aria-pressed',String(!v.paused));
    }
    const video=videos.find(v=>v.dataset.cedarStrength===select.value);
    status.textContent=original?'Original map shown.':`${select.selectedOptions[0].textContent} at map scale · ${video.paused?'paused':'playing'} · study only`;
  };
  for(const video of videos){video.defaultPlaybackRate=.91;video.playbackRate=.91;video.addEventListener('loadedmetadata',()=>video.playbackRate=.91);video.addEventListener('play',sync);video.addEventListener('pause',sync);}
  for(const button of document.querySelectorAll('[data-cedar-toggle]'))button.onclick=async()=>{
    const video=videos.find(v=>v.dataset.cedarStrength===button.dataset.cedarToggle);
    if(video.paused){try{await video.play();}catch{status.textContent='Playback could not start. Try Play / restart all three.';}}else video.pause();sync();
  };
  document.getElementById('cedarStrengthPlay').onclick=async()=>{
    for(const v of videos)v.currentTime=0;
    const results=await Promise.allSettled(videos.map(v=>v.play()));
    sync();if(results.some(r=>r.status==='rejected'))status.textContent='Use the individual video play buttons to start any paused comparison.';
  };
  document.getElementById('cedarStrengthPause').onclick=()=>{for(const v of videos)v.pause();sync();};
  document.getElementById('cedarStrengthOriginal').onclick=e=>{original=!original;e.currentTarget.setAttribute('aria-pressed',String(original));e.currentTarget.textContent=original?'Show selected animation':'Compare original';sync();};
  select.onchange=sync;
  const load=async src=>{const im=new Image();im.src=src;await im.decode();return im;};
  try{
    const [map,mask]=await Promise.all([load('adventure/assets/sumerian-overworld-v1.png'),load('adventure/assets/ambient-sections/cedar-grove-mask-v2.png')]);
    const layer=document.createElement('canvas');layer.width=576;layer.height=384;const lc=layer.getContext('2d');lc.drawImage(mask,0,0);
    const pixels=lc.getImageData(0,0,576,384);for(let i=0;i<pixels.data.length;i+=4)pixels.data[i+3]=pixels.data[i];
    const matte=document.createElement('canvas');matte.width=576;matte.height=384;matte.getContext('2d').putImageData(pixels,0,0);
    function draw(){
      requestAnimationFrame(draw);if(document.hidden)return;
      const rect=canvas.getBoundingClientRect();if(rect.bottom<0||rect.top>innerHeight)return;
      ctx.drawImage(map,0,0);
      const video=videos.find(v=>v.dataset.cedarStrength===select.value);
      if(!original&&video.readyState>=2){lc.globalCompositeOperation='source-over';lc.clearRect(0,0,576,384);lc.drawImage(video,0,0,576,384);lc.globalCompositeOperation='destination-in';lc.drawImage(matte,0,0);ctx.drawImage(layer,0,0);}
    }
    sync();requestAnimationFrame(draw);
  }catch{status.textContent='The map comparison could not load. The individual clips above are still available.';}
})();
