'use strict';
(async()=>{
  const large=document.getElementById('rigLarge'),small=document.getElementById('rigSmall');
  const play=document.getElementById('rigPlay'),still=document.getElementById('rigStill');
  const amount=document.getElementById('rigAmount'),amountValue=document.getElementById('rigAmountValue');
  const scrub=document.getElementById('rigFrame'),frameValue=document.getElementById('rigFrameValue'),status=document.getElementById('rigStatus');
  let playing=false,compare=false,elapsed=0,last=null;
  try{
    const response=await fetch('assets/cedar-rig-v1/manifest.json');if(!response.ok)throw new Error('Manifest missing');
    const spec=await response.json();
    const layers=await Promise.all(spec.layers.map(async layer=>{const image=new Image();image.src='assets/cedar-rig-v1/'+layer.image;await image.decode();return {...layer,image};}));
    const angle=(layer,t)=>layer.amplitudeDegrees*Math.PI/180*(.8*Math.sin(2*Math.PI*t/spec.periodSeconds+layer.phase)+.2*Math.sin(4*Math.PI*t/spec.periodSeconds+.8*layer.phase));
    function drawTree(ctx,t,gain){
      for(const layer of layers){
        ctx.save();const a=compare?0:angle(layer,t)*gain;
        ctx.translate(...layer.pivot);ctx.rotate(a);ctx.translate(-layer.pivot[0],-layer.pivot[1]);
        ctx.drawImage(layer.image,...layer.rect);ctx.restore();
      }
    }
    function draw(){
      const gain=Number(amount.value);
      for(const [canvas,scale]of [[large,1],[small,.4]]){
        const ctx=canvas.getContext('2d');ctx.clearRect(0,0,512,512);ctx.fillStyle='#e4d2aa';ctx.fillRect(0,0,512,512);
        ctx.save();ctx.translate(256*(1-scale),435*(1-scale));ctx.scale(scale,scale);drawTree(ctx,elapsed,gain);ctx.restore();
      }
      const frame=Math.floor((elapsed%spec.periodSeconds)/spec.periodSeconds*spec.frameCount);
      scrub.value=String(frame);frameValue.value=`${frame+1} / ${spec.frameCount}`;
      amountValue.value=`${Math.round(gain*100)}%`;
      play.textContent=playing?'Pause breeze':'Play breeze';play.setAttribute('aria-pressed',String(playing));
      status.textContent=compare?'Still assembled pose.':`${playing?'Playing':'Paused'} · ${spec.periodSeconds}-second cycle · rigid pieces · trunk fixed`;
    }
    play.onclick=()=>{playing=!playing;last=null;draw();};
    still.onclick=()=>{compare=!compare;still.setAttribute('aria-pressed',String(compare));still.textContent=compare?'Show moving pose':'Compare still pose';draw();};
    amount.oninput=draw;
    scrub.oninput=()=>{playing=false;last=null;elapsed=Number(scrub.value)/spec.frameCount*spec.periodSeconds;draw();};
    document.addEventListener('visibilitychange',()=>{last=null;});
    for(const layer of layers){
      const box=document.createElement('div'),canvas=document.createElement('canvas'),caption=document.createElement('p');canvas.width=512;canvas.height=512;
      canvas.getContext('2d').drawImage(layer.image,...layer.rect);caption.textContent=layer.name;box.append(canvas,caption);document.getElementById('rigParts').append(box);
    }
    function tick(now){
      requestAnimationFrame(tick);
      if(document.hidden){last=null;return;}
      if(playing&&last!==null)elapsed=(elapsed+Math.min((now-last)/1000,.1))%spec.periodSeconds;
      last=now;if(playing)draw();
    }
    draw();requestAnimationFrame(tick);
  }catch(error){status.textContent='The tree layers could not load. Please refresh the study.';console.error(error);}
})();
