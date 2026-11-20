'use strict';
(async()=>{
  const canvas=document.getElementById('waterV2Scene'),ctx=canvas.getContext('2d'),video=document.getElementById('waterV2Video');
  const map=new Image();map.src='adventure/assets/riverside-quarter-v1.webp';
  try{await map.decode();}catch{return;}
  const patch=document.createElement('canvas');patch.width=62;patch.height=70;const pc=patch.getContext('2d');
  const mask=document.createElement('canvas');mask.width=62;mask.height=70;const mc=mask.getContext('2d'),data=mc.createImageData(62,70);
  for(let y=0;y<70;y++)for(let x=0;x<62;x++){
    const i=(y*62+x)*4;data.data[i+3]=Math.round(255*Math.min(1,Math.max(0,Math.min(x,y,61-x,69-y)/5)));
  }
  mc.putImageData(data,0,0);
  let active=true,focus=false;
  const toggle=document.getElementById('waterV2Toggle');
  toggle.onclick=()=>{active=!active;toggle.textContent=active?'Show original water':'Show generated water';toggle.setAttribute('aria-pressed',String(active));};
  document.getElementById('waterV2Focus').onclick=e=>{
    focus=!focus;canvas.width=focus?480:1536;canvas.height=focus?672:1024;
    canvas.style.aspectRatio=focus?'480 / 672':'1.5';canvas.style.maxWidth=focus?'600px':'100%';
    e.currentTarget.textContent=focus?'Show whole map':'Inspect pier context';
    e.currentTarget.setAttribute('aria-pressed',String(focus));
  };
  function draw(){
    ctx.setTransform(1,0,0,1,0,0);ctx.clearRect(0,0,canvas.width,canvas.height);
    if(focus)ctx.translate(-1056,-288);
    ctx.drawImage(map,0,0);
    if(active&&video.readyState>=2){
      pc.globalCompositeOperation='source-over';pc.clearRect(0,0,62,70);pc.drawImage(video,0,0,62,70);
      pc.globalCompositeOperation='destination-in';pc.drawImage(mask,0,0);ctx.drawImage(patch,1474,680);
    }
    ctx.strokeStyle='#ffe6a0';ctx.lineWidth=1;ctx.strokeRect(1473,679,64,72);
    requestAnimationFrame(draw);
  }
  requestAnimationFrame(draw);
})();
