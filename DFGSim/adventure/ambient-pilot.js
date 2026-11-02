'use strict';
// Study only: generated pixels are admitted exclusively through the reviewed matte.
(async function () {
  const canvas=document.getElementById('generatedScene'),ctx=canvas.getContext('2d');
  const video=document.getElementById('generatedWater');
  const status=document.getElementById('generatedStatus');
  const layer=document.createElement('canvas');layer.width=480;layer.height=672;
  const lc=layer.getContext('2d');
  const load=async src=>{const image=new Image();image.src=src;await image.decode();return image;};
  try {
    const [map,mask]=await Promise.all([
      load('adventure/assets/riverside-quarter-v1.webp'),
      load('adventure/assets/ambient-pilot/riverside-water-mask-v1.png')
    ]);
    // The supplied PNG stores opacity as grayscale, rather than its alpha channel.
    lc.drawImage(mask,0,0);const pixels=lc.getImageData(0,0,480,672);
    for(let i=0;i<pixels.data.length;i+=4)pixels.data[i+3]=pixels.data[i];
    const matte=document.createElement('canvas');matte.width=480;matte.height=672;
    matte.getContext('2d').putImageData(pixels,0,0);
    let focus=false,animated=false;
    document.getElementById('generatedFocus').onclick=e=>{
      focus=!focus;e.currentTarget.setAttribute('aria-pressed',String(focus));
      e.currentTarget.textContent=focus?'Show whole map':'Inspect pier crop';
      canvas.width=focus?480:1536;canvas.height=focus?672:1024;
      canvas.style.aspectRatio=focus?'480 / 672':'1.5';canvas.style.maxWidth=focus?'600px':'100%';
    };
    document.getElementById('generatedToggle').onclick=async e=>{
      const button=e.currentTarget;
      animated=!animated;
      if(animated){try{await video.play();}catch{animated=false;}}else video.pause();
      button.setAttribute('aria-pressed',String(animated));
      button.textContent=animated?'Show original map':'Play masked take';
      status.textContent=animated?'Masked Kling take playing · water motion is too subtle for use in the adventure.':'Original map shown · use Play masked take to compare.';
    };
    document.addEventListener('visibilitychange',()=>{if(document.hidden)video.pause();else if(animated)video.play().catch(()=>{});});
    function draw(){
      ctx.setTransform(1,0,0,1,0,0);ctx.clearRect(0,0,1536,1024);
      if(focus)ctx.setTransform(1,0,0,1,-1056,-288);
      ctx.drawImage(map,0,0);
      if(animated&&video.readyState>=2){
        lc.globalCompositeOperation='source-over';lc.clearRect(0,0,480,672);lc.drawImage(video,0,0,480,672);
        lc.globalCompositeOperation='destination-in';lc.drawImage(matte,0,0);
        ctx.drawImage(layer,1056,288);
      }
      requestAnimationFrame(draw);
    }
    status.textContent='Ready · original map shown. This take has insufficient water motion; it is not enabled in the adventure.';
    requestAnimationFrame(draw);
  }catch(error){status.textContent='Pilot preview could not load. Use the video links below to inspect the files.';}
})();
