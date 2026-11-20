'use strict';
(async()=>{
  const canvas=document.getElementById('sectionScene'),ctx=canvas.getContext('2d');
  const video=document.getElementById('sectionVideo'),status=document.getElementById('sectionStatus');
  const play=document.getElementById('sectionPlay'),compare=document.getElementById('sectionOriginal');
  const load=async src=>{const im=new Image();im.src=src;await im.decode();return im;};
  const layer=document.createElement('canvas');layer.width=480;layer.height=672;
  const lc=layer.getContext('2d');
  try{
    const [map,mask,oldMask]=await Promise.all([load('adventure/assets/riverside-quarter-v1.webp'),load('adventure/assets/ambient-pilot/riverside-section-mask-v3.png'),load('adventure/assets/ambient-pilot/riverside-section-mask-v1.png')]);
    const makeMatte=im=>{
      lc.globalCompositeOperation='source-over';lc.clearRect(0,0,480,672);lc.drawImage(im,0,0);
      const pixels=lc.getImageData(0,0,480,672);
      for(let i=0;i<pixels.data.length;i+=4){pixels.data[i+3]=pixels.data[i];pixels.data[i]=255;pixels.data[i+1]=75;pixels.data[i+2]=165;}
      const out=document.createElement('canvas');out.width=480;out.height=672;out.getContext('2d').putImageData(pixels,0,0);return out;
    };
    const mattes={latest:makeMatte(mask),earlier:makeMatte(oldMask)};
    let running=false,original=false,showMask=false,whole=false,take='latest';
    const sync=()=>{
      play.textContent=running?'Pause section':'Play section';play.setAttribute('aria-pressed',String(running));
      compare.textContent=original?'Return to animated section':'Compare original';compare.setAttribute('aria-pressed',String(original));
      status.textContent=original?'Original map · same framing.':showMask?'Pink = replacement region. Original pier and post pixels are protected.':take==='latest'?(running?'Kling 4.0 · full-section reed and water motion · inspect the loop boundary.':'Ready · latest Kling 4.0 full-section take. Play to review reeds, water and the loop.'):(running?'Earlier Kling 3.0 salvage · two reed clusters moving; water mostly static.':'Ready · earlier below-pier salvage for comparison.');
    };
    play.onclick=async()=>{
      if(running){video.pause();running=false;}else{try{await video.play();running=true;}catch{status.textContent='Playback could not start. Try the source video link below.';return;}}
      sync();
    };
    compare.onclick=()=>{original=!original;sync();};
    document.getElementById('sectionTake').onchange=async e=>{
      take=e.target.value;video.pause();
      video.src=take==='latest'?'adventure/assets/ambient-pilot/riverside-section-registered-v3.mp4':'adventure/assets/ambient-pilot/riverside-water-registered-v1.mp4';
      if(running){try{await video.play();}catch{running=false;}}
      sync();
    };
    document.getElementById('sectionMask').onclick=e=>{showMask=!showMask;e.currentTarget.setAttribute('aria-pressed',String(showMask));sync();};
    document.getElementById('sectionView').onclick=e=>{
      whole=!whole;canvas.width=whole?1536:480;canvas.height=whole?1024:672;
      canvas.style.aspectRatio=whole?'1.5':'480 / 672';canvas.style.maxWidth=whole?'100%':'600px';e.currentTarget.textContent=whole?'Inspect riverside section':'Show whole map';
      e.currentTarget.setAttribute('aria-pressed',String(whole));
    };
    document.addEventListener('visibilitychange',()=>{if(document.hidden)video.pause();else if(running)video.play().catch(()=>{running=false;sync();});});
    function draw(){
      ctx.setTransform(1,0,0,1,0,0);ctx.clearRect(0,0,canvas.width,canvas.height);
      if(!whole)ctx.setTransform(1,0,0,1,-1056,-288);
      ctx.drawImage(map,0,0);
      if(!original&&video.readyState>=2){
        lc.globalCompositeOperation='source-over';lc.clearRect(0,0,480,672);lc.drawImage(video,0,0,480,672);
        lc.globalCompositeOperation='destination-in';lc.drawImage(mattes[take],0,0);ctx.drawImage(layer,1056,288);
      }
      if(showMask){ctx.globalAlpha=.5;ctx.drawImage(mattes[take],1056,288);ctx.globalAlpha=1;}
      requestAnimationFrame(draw);
    }
    sync();requestAnimationFrame(draw);
  }catch{status.textContent='Unable to load this section. Reload the page or inspect the source video below.';}
})();
