"""Pack existing transparent idle frames for deterministic canvas playback."""
from pathlib import Path
from PIL import Image
import json, math
ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'adventure/assets/overworld-idles';OUT.mkdir(exist_ok=True)
fighters={
 'iddin':('assets/fighter-red-idle.webp','assets/fighter-red-transparent.webp',.11),
 'imdugud':('roguelike/assets/animations/imdugud-idle.webp','roguelike/assets/sumerian/imdugud-v1.png',.39),
 'huwawa':('roguelike/assets/animations/huwawa-idle.webp','roguelike/assets/sumerian/huwawa-v1.png',.67),
 'bull-of-heaven':('roguelike/assets/animations/bull-of-heaven-idle.webp','roguelike/assets/sumerian/bull-of-heaven-v1.png',.83)}
manifest={}
for name,(source,still,phase)in fighters.items():
 im=Image.open(ROOT/source);frames=[];durations=[];box=None
 for i in range(im.n_frames):
  im.seek(i);frame=im.convert('RGBA');frames.append(frame.copy());durations.append(im.info.get('duration',55));b=frame.getbbox()
  if b:box=b if box is None else (min(box[0],b[0]),min(box[1],b[1]),max(box[2],b[2]),max(box[3],b[3]))
 h=256;w=math.ceil((box[2]-box[0])*h/(box[3]-box[1]));cols=8
 sheet=Image.new('RGBA',(w*cols,h*math.ceil(len(frames)/cols)))
 for i,frame in enumerate(frames):sheet.paste(frame.crop(box).resize((w,h),Image.Resampling.LANCZOS),((i%cols)*w,(i//cols)*h))
 sheet.save(OUT/(name+'-idle-atlas-v1.webp'),'WEBP',quality=88,method=6)
 portrait=Image.open(ROOT/still).convert('RGBA');b=portrait.getbbox();pw,ph=portrait.size
 manifest[name]={'src':'adventure/assets/overworld-idles/'+name+'-idle-atlas-v1.webp','frameWidth':w,'frameHeight':h,'columns':cols,'frames':len(frames),'durationsMs':durations,'durationMs':sum(durations),'phase':phase,'visibleHeight':(b[3]-b[1])/ph,'bottomGap':(ph-b[3])/ph,'centerOffset':((b[0]+b[2])/2-pw/2)/ph,'source':source,'sourceCrop':list(box)}
 print(name,w,h,len(frames),sum(durations))
(OUT/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
