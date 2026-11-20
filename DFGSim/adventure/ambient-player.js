'use strict';
// Map-coordinate overlays. A failed or disabled clip always leaves the source art.
window.DFGAdventureAmbient=(()=>{
  const sections={
    riverside:{box:[1056,288,480,672],video:'adventure/assets/ambient-pilot/riverside-section-registered-v3.mp4',mask:'adventure/assets/ambient-pilot/riverside-section-mask-v3.png',rate:1,phase:.37},
    courtyard:{box:[400,590,800,434],video:'adventure/assets/ambient-sections/courtyard-canals-registered-v1.mp4',mask:'adventure/assets/ambient-sections/courtyard-canals-mask-v1.png',rate:.87,phase:.19},
    sanctuary:{box:[320,0,896,384],video:'adventure/assets/ambient-sections/sanctuary-altar-registered-v1.mp4',mask:'adventure/assets/ambient-sections/sanctuary-altar-mask-v1.png',rate:.73,phase:.63},
    'cedar-grove':{zone:'courtyard',box:[0,0,576,384],video:'adventure/assets/ambient-sections/cedar-grove-restrained-v2.mp4',mask:'adventure/assets/ambient-sections/cedar-grove-mask-v2.png',rate:.91,phase:.47},
    'temple-court':{zone:'courtyard',box:[848,0,688,704],video:'adventure/assets/ambient-sections/temple-court-registered-v2.mp4',mask:'adventure/assets/ambient-sections/temple-court-mask-v2.png',rate:.79,phase:.71}
  };
  function create(){
    const entries=new Map();let wanted=new Set();
    function prepare(id){
      if(entries.has(id))return entries.get(id);
      const spec=sections[id],video=document.createElement('video'),layer=document.createElement('canvas');
      const [, ,w,h]=spec.box;layer.width=w;layer.height=h;
      video.muted=true;video.defaultMuted=true;video.loop=true;video.playsInline=true;video.preload='auto';video.defaultPlaybackRate=spec.rate;video.playbackRate=spec.rate;
      // WebKit can play a display:none video without making its frames available
      // to canvas. Keep a tiny, non-interactive decoding surface in the viewport.
      video.setAttribute('aria-hidden','true');video.setAttribute('tabindex','-1');video.dataset.ambientSection=id;
      Object.assign(video.style,{position:'fixed',left:'0',top:'0',width:'1px',height:'1px',opacity:'0.001',pointerEvents:'none'});document.body.append(video);
      const entry={spec,video,layer,ctx:layer.getContext('2d'),matte:null,failed:false,pending:false,lastFrame:-1,paintTime:-1};entries.set(id,entry);
      const fail=()=>{entry.failed=true;video.pause();};video.addEventListener('error',fail);
      video.addEventListener('loadedmetadata',()=>{video.playbackRate=spec.rate;if(Number.isFinite(video.duration)&&video.duration>0)video.currentTime=video.duration*spec.phase;});
      const image=new Image();image.src=spec.mask;
      image.decode().then(()=>{
        const matte=document.createElement('canvas');matte.width=w;matte.height=h;const mc=matte.getContext('2d');mc.drawImage(image,0,0);
        const pixels=mc.getImageData(0,0,w,h);for(let i=0;i<pixels.data.length;i+=4)pixels.data[i+3]=pixels.data[i];mc.putImageData(pixels,0,0);entry.matte=matte;
      }).catch(fail);
      video.src=spec.video;return entry;
    }
    function pause(){wanted.clear();for(const e of entries.values())e.video.pause();}
    document.addEventListener('visibilitychange',()=>{if(document.hidden)pause();});
    function draw(ctx,{zone,active,enabled=true,view,time}){
      const visible=Object.entries(sections).filter(([id,spec])=>(spec.zone||id)===zone&&view.x<spec.box[0]+spec.box[2]&&view.x+view.w/view.scale>spec.box[0]&&view.y<spec.box[1]+spec.box[3]&&view.y+view.h/view.scale>spec.box[1]);
      wanted=new Set(enabled&&active&&!document.hidden?visible.map(([id])=>id):[]);
      for(const [id,e]of entries)if(!wanted.has(id))e.video.pause();
      for(const [id]of visible){
      if(!wanted.has(id)){const e=entries.get(id);if(enabled&&e&&!e.failed&&e.lastFrame>=0)ctx.drawImage(e.layer,e.spec.box[0],e.spec.box[1]);continue;}
      const e=prepare(id);if(e.failed)continue;
      if(e.video.paused&&!e.pending){e.pending=true;e.video.play().catch(error=>{if(error.name!=='AbortError')e.failed=true;}).finally(()=>{e.pending=false;if(!wanted.has(id))e.video.pause();});}
      if(!e.matte||e.video.readyState<2)continue;
      if(e.lastFrame!==e.video.currentTime&&time-e.paintTime>=1000/24){
        try{
          e.ctx.globalCompositeOperation='source-over';e.ctx.clearRect(0,0,e.layer.width,e.layer.height);e.ctx.drawImage(e.video,0,0,e.layer.width,e.layer.height);
          e.ctx.globalCompositeOperation='destination-in';e.ctx.drawImage(e.matte,0,0);e.lastFrame=e.video.currentTime;e.paintTime=time;
        }catch{e.failed=true;e.video.pause();continue;}
      }
      if(e.lastFrame>=0)ctx.drawImage(e.layer,e.spec.box[0],e.spec.box[1]);
      }
    }
    return {draw,pause};
  }
  return {sections,create};
})();
