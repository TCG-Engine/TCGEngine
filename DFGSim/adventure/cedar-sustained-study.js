'use strict';
(async()=>{
  const video=document.getElementById('cedarSustainedVideo');
  if(!video)return;
  const canvas=document.getElementById('cedarSustainedMap'),ctx=canvas.getContext('2d');
  const play=document.getElementById('cedarSustainedPlay'),compare=document.getElementById('cedarSustainedOriginal');
  const status=document.getElementById('cedarSustainedStatus');
  const speed=document.getElementById('cedarSustainedSpeed');
  const applySpeed=()=>{video.defaultPlaybackRate=Number(speed.value);video.playbackRate=Number(speed.value);};
  let original=false;
  const sync=()=>{
    play.textContent=video.paused?'Play rejected reference':'Pause reference';
    play.setAttribute('aria-pressed',String(!video.paused));
    status.textContent=original?'Original map shown.':`Active middle section at ${Math.round(video.playbackRate*100)}% speed - ${video.paused?'paused':'playing'} - rejected reference`;
  };
  applySpeed();video.addEventListener('loadedmetadata',()=>{applySpeed();sync();});
  speed.onchange=()=>{applySpeed();sync();};
  play.onclick=async()=>{try{if(video.paused)await video.play();else video.pause();sync();}catch{status.textContent='Playback could not start. Try again.';}};
  compare.onclick=()=>{original=!original;compare.setAttribute('aria-pressed',String(original));compare.textContent=original?'Show reference':'Compare original map';sync();};
  video.addEventListener('play',sync);video.addEventListener('pause',sync);
  document.addEventListener('visibilitychange',()=>{if(document.hidden)video.pause();});
  const load=async src=>{const image=new Image();image.src=src;await image.decode();return image;};
  try{
    const [map,mask]=await Promise.all([load('adventure/assets/sumerian-overworld-v1.png'),load('adventure/assets/ambient-sections/cedar-grove-mask-v5.png')]);
    const layer=document.createElement('canvas');layer.width=576;layer.height=384;
    const lc=layer.getContext('2d');lc.drawImage(mask,0,0);
    const pixels=lc.getImageData(0,0,576,384);for(let i=0;i<pixels.data.length;i+=4)pixels.data[i+3]=pixels.data[i];
    const matte=document.createElement('canvas');matte.width=576;matte.height=384;matte.getContext('2d').putImageData(pixels,0,0);
    function draw(){
      requestAnimationFrame(draw);if(document.hidden)return;
      const bounds=canvas.getBoundingClientRect();if(bounds.bottom<0||bounds.top>innerHeight)return;
      ctx.drawImage(map,0,0);
      if(!original&&video.readyState>=2){lc.globalCompositeOperation='source-over';lc.clearRect(0,0,576,384);lc.drawImage(video,0,0,576,384);lc.globalCompositeOperation='destination-in';lc.drawImage(matte,0,0);ctx.drawImage(layer,0,0);}
    }
    sync();requestAnimationFrame(draw);
  }catch{status.textContent='Map preview could not load. The new clip above is still available.';}
})();
