'use strict';
// Review prototype only; the rejected object-warp compositor is disabled in gameplay.
window.DFGBackgroundMotion=(()=>{
 const patches={courtyard:[
  {id:'canal-west',name:'Western canal surface',box:[430,719,225,158],seconds:7.3,phase:.13},
  {id:'canal-east',name:'Eastern canal surface',box:[775,710,310,200],seconds:11.9,phase:.64}
 ],riverside:[
  {id:'river-channel',name:'Open river around the pier',box:[1305,360,231,510],seconds:10.9,phase:.42},
  {id:'river-south',name:'Southern river surface',box:[650,866,760,158],seconds:17.3,phase:.77}
 ]};
 const smooth=(a,b,x)=>{const t=Math.max(0,Math.min(1,(x-a)/(b-a)));return t*t*(3-2*t);};
 function surface(w,h){const c=document.createElement('canvas');c.width=w;c.height=h;return c;}
 function make(bg,p){
  const [x,y,w,h]=p.box,source=surface(w,h),mask=surface(w,h),tint=surface(w,h),output=surface(w,h);
  const sc=source.getContext('2d');sc.drawImage(bg,x,y,w,h,0,0,w,h);const pixels=sc.getImageData(0,0,w,h),data=pixels.data;
  const alpha=new Float32Array(w*h),horizontal=new Float32Array(w*h),safe=new Float32Array(w*h),radius=4;
  for(let i=0;i<alpha.length;i++){const r=data[i*4]/255,g=data[i*4+1]/255,b=data[i*4+2]/255;
   alpha[i]=Math.min(smooth(.10,.22,g-r),smooth(.13,.25,b-r),1-smooth(.12,.22,Math.abs(g-b)));
  }
  // Keep a four-pixel untouched guard band beside every shoreline and object.
  for(let py=0;py<h;py++)for(let px=0;px<w;px++){let a=1;for(let n=-radius;n<=radius;n++)a=Math.min(a,px+n<0||px+n>=w?0:alpha[py*w+px+n]);horizontal[py*w+px]=a;}
  for(let py=0;py<h;py++)for(let px=0;px<w;px++){let a=1;for(let n=-radius;n<=radius;n++)a=Math.min(a,py+n<0||py+n>=h?0:horizontal[(py+n)*w+px]);safe[py*w+px]=a*smooth(0,14,Math.min(px,py,w-1-px,h-1-py));}
  for(let i=0;i<safe.length;i++){data[i*4]=data[i*4+1]=data[i*4+2]=255;data[i*4+3]=Math.round(safe[i]*255);}
  mask.getContext('2d').putImageData(pixels,0,0);const tc=tint.getContext('2d');tc.fillStyle='#ff64c5';tc.fillRect(0,0,w,h);tc.globalCompositeOperation='destination-in';tc.drawImage(mask,0,0);
  return {source,mask,tint,output,ctx:output.getContext('2d'),last:-Infinity};
 }
 function create(){
  const cache=new Map();let clock=0,lastTime=null,wasActive=false,frames=0;
  const tile=(bg,p)=>{const key=bg.src+'#'+p.id;if(!cache.has(key))cache.set(key,make(bg,p));return cache.get(key);};
  return {
   source(bg,p){return tile(bg,p).source;},mask(bg,p){return tile(bg,p).tint;},
   draw(ctx,bg,zone,time,{active=true,showMask=false}={}){
    const dt=lastTime===null?0:Math.max(0,Math.min(80,time-lastTime));lastTime=time;if(active&&wasActive)clock+=dt;wasActive=active;if(!bg?.naturalWidth)return;
    for(const p of patches[zone]??[]){const t=tile(bg,p),[x,y,w,h]=p.box;
     if(showMask){ctx.save();ctx.globalAlpha=.65;ctx.drawImage(t.tint,x,y);ctx.restore();continue;}if(!active)continue;
     if(clock-t.last>=1000/24){const c=t.ctx;c.clearRect(0,0,w,h);const count=Math.max(6,Math.round(w*h/4800));
      for(let i=0;i<count;i++){
       const u=(clock/(p.seconds*1000)+p.phase+i*.61803398875)%1,envelope=Math.sin(Math.PI*u)**2;
       const cx=14+((i*.38196601125)%1)*(w-28)+Math.sin(u*Math.PI*2+i)*8,cy=h*(1-u),span=15+(i%4)*7;
       c.strokeStyle=`rgba(218,255,247,${.40*envelope})`;c.lineWidth=2;c.lineCap='round';c.beginPath();c.moveTo(cx-span,cy);c.quadraticCurveTo(cx,cy+4,cx+span,cy-1);c.stroke();
       c.strokeStyle=`rgba(218,255,247,${.19*envelope})`;c.lineWidth=1;c.beginPath();c.moveTo(cx-span*.55,cy+6);c.quadraticCurveTo(cx,cy+8,cx+span*.45,cy+5);c.stroke();
      }
      c.globalCompositeOperation='destination-in';c.drawImage(t.mask,0,0);c.globalCompositeOperation='source-over';t.last=clock;frames++;
     }ctx.drawImage(t.output,x,y);
    }
   },get status(){return {cachedPatches:cache.size,frames,clockMs:clock};}
  };
 }
 return {patches,create};
})();
