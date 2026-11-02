'use strict';
window.DFGOverworldIdles=(()=>{
  function create(){
    let manifest=null,loading=false;const entries=new Map();
    function draw(ctx,id,x,y,height,{time,enabled,active,visible}){
      if(!enabled||!visible)return false;
      if(!manifest){
        if(!loading){loading=true;fetch('adventure/assets/overworld-idles/manifest.json').then(r=>{if(!r.ok)throw Error('Idle manifest unavailable');return r.json();}).then(value=>manifest=value).catch(()=>{});}
        return false;
      }
      const spec=manifest[id];if(!spec)return false;
      let entry=entries.get(id);
      if(!entry){
        if(!active)return false;
        const image=new Image();entry={image,ready:false,clock:spec.durationMs*spec.phase,lastTime:time};entries.set(id,entry);
        image.src=spec.src;image.decode().then(()=>entry.ready=true).catch(()=>{});
      }
      if(active&&!document.hidden&&time-entry.lastTime<100)entry.clock=(entry.clock+Math.max(0,time-entry.lastTime))%spec.durationMs;
      entry.lastTime=time;if(!entry.ready)return false;
      let frame=0,end=spec.durationsMs[0];while(frame<spec.frames-1&&entry.clock>=end)end+=spec.durationsMs[++frame];
      const h=height*spec.visibleHeight,w=h*spec.frameWidth/spec.frameHeight;
      ctx.drawImage(entry.image,(frame%spec.columns)*spec.frameWidth,Math.floor(frame/spec.columns)*spec.frameHeight,spec.frameWidth,spec.frameHeight,x+height*spec.centerOffset-w/2,y-height*spec.bottomGap-h,w,h);
      return true;
    }
    return {draw};
  }
  return {create};
})();
