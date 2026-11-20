"""Prepare separated cutouts and export a rigid-layer cedar animation pilot."""
from pathlib import Path
import io, json, math, shutil, subprocess, sys, zipfile
import cv2
import numpy as np
from PIL import Image, ImageDraw

adventure=Path(__file__).resolve().parents[1]
output=adventure/'assets'/'cedar-rig-v1';output.mkdir(exist_ok=True)
source=Path(sys.argv[1]) if len(sys.argv)>1 else output/'parts-source.png'
if source.resolve()!=(output/'parts-source.png').resolve():shutil.copyfile(source,output/'parts-source.png')
sheet=Image.open(output/'parts-source.png').convert('RGBA')
alpha=np.asarray(sheet)[:,:,3]
count,labels,stats,centers=cv2.connectedComponentsWithStats(np.uint8(alpha>100))
parts=[(i,s,c) for i,(s,c) in enumerate(zip(stats,centers)) if i and s[4]>1000]
assert len(parts)==4, f'Expected four separated parts, got {len(parts)}'
parts.sort(key=lambda p:(p[2][1]>sheet.height*.6,p[2][0]))
settings=[
 ('trunk','Trunk and roots',[165,80,190,355],[256,435],0,0),
 ('lower','Lower foliage',[101,210,310,173],[256,250],1.1,0),
 ('middle','Middle foliage',[124,145,264,172],[256,195],1.35,1.6),
 ('top','Top foliage',[158,67,196,172],[256,180],1.6,3.2),
]
layers=[];images=[]
for part,(slug,name,rect,pivot,amplitude,phase) in zip(parts,settings):
    index,stat,center=part;x,y,w,h,_=map(int,stat)
    crop=sheet.crop((x-2,y-2,x+w+2,y+h+2))
    crop.save(output/f'{slug}.png')
    subprocess.run([sys.executable,str(adventure.parents[1]/'DevTools'/'png-to-webp.py'),str(output/f'{slug}.png'),'--lossless'],check=True)
    images.append(crop.resize((rect[2],rect[3]),Image.Resampling.LANCZOS))
    layers.append(dict(id=slug,name=name,image=f'{slug}.webp',sourceRect=[x-2,y-2,w+4,h+4],rect=rect,pivot=pivot,amplitudeDegrees=amplitude,phase=phase))

period=8;frame_count=120
def render(t,gain=1):
    t=t%period
    canvas=Image.new('RGBA',(512,512))
    for layer,image in zip(layers,images):
        angle=layer['amplitudeDegrees']*(.8*math.sin(2*math.pi*t/period+layer['phase'])+.2*math.sin(4*math.pi*t/period+.8*layer['phase']))*gain
        pane=Image.new('RGBA',(512,512));pane.alpha_composite(image,tuple(layer['rect'][:2]))
        if angle:pane=pane.rotate(-angle,Image.Resampling.BICUBIC,center=tuple(layer['pivot']))
        canvas.alpha_composite(pane)
    return canvas
frames=[render(i*period/frame_count) for i in range(frame_count)]
with zipfile.ZipFile(output/'frames.zip','w',zipfile.ZIP_DEFLATED) as archive:
    for i,frame in enumerate(frames):
        buf=io.BytesIO();frame.save(buf,format='PNG');archive.writestr(f'cedar-{i:03d}.png',buf.getvalue())
    archive.writestr('README.txt','120 transparent PNG frames, 512x512. Play at 15 fps for an eight-second loop. Frame 119 advances to frame 000. The trunk is fixed; foliage layers undergo rigid rotations only.\n')
background=Image.new('RGBA',(512,512),'#e4d2aa')
previews=[Image.alpha_composite(background,f).convert('RGB') for f in frames]
previews[0].save(output/'preview.webp',save_all=True,append_images=previews[1:],duration=[67,67,66]*40,loop=0,quality=85,method=4)
contact=Image.new('RGB',(1024,1024),'#e4d2aa');draw=ImageDraw.Draw(contact)
for n,i in enumerate(range(0,frame_count,10)):
    col,row=n%4,n//4;contact.paste(previews[i].resize((256,256),Image.Resampling.LANCZOS),(col*256,row*256));draw.text((col*256+10,row*256+10),f'Frame {i:03d}',fill='#3b2e22')
contact=contact.crop((0,0,1024,768));contact.save(output/'contact-sheet.webp',lossless=True)
fixed=np.asarray(frames[0])[420:435,235:280].astype(int)
fixed_error=max(int(np.abs(np.asarray(f)[420:435,235:280].astype(int)-fixed).max()) for f in frames)
assert fixed_error==0,'Root pixels must remain fixed'
endpoint_error=int(np.abs(np.asarray(render(0)).astype(int)-np.asarray(render(period)).astype(int)).max())
assert endpoint_error==0,'Periodic endpoint must match'
report=dict(status='one_tree_study_only',artPreparation='Built-in image_gen: transparent separated-parts sheet, followed by removal of an unwanted trunk on one foliage piece. Parts are cropped and registered locally.',source='parts-source.png',periodSeconds=period,frameCount=frame_count,exportFps=frame_count/period,canvas=[512,512],layers=layers,method='Fixed trunk plus three overlapping rigid foliage pieces; rotation only, no scaling or per-pixel displacement. Canvas preview and exported frames share the layer settings and periodic angle function.',checks=dict(protectedRootMaxChannelDifference=fixed_error,periodicEndpointMaxChannelDifference=endpoint_error),liveGameChanged=False,klingCreditsSpent=0)
(output/'manifest.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print(json.dumps(report['checks']))
