"""Use a generated motion guide without importing its altered tree artwork."""
from pathlib import Path
import cv2,numpy as np,json,subprocess
P=Path(__file__).resolve().parents[1]/'assets'/'ambient-sections'
ref=cv2.imread(str(P/'cedar-grove-input-v1.png'));h,w=ref.shape[:2]
cap=cv2.VideoCapture(str(P/'cedar-grove-registered-v2.mp4'));fps=cap.get(cv2.CAP_PROP_FPS);frames=[]
while True:
    ok,frame=cap.read()
    if not ok:break
    frames.append(frame)
cap.release();g0=cv2.cvtColor(frames[0],cv2.COLOR_BGR2GRAY)
flows=[]
for frame in frames:
    flow=cv2.calcOpticalFlowFarneback(cv2.cvtColor(frame,cv2.COLOR_BGR2GRAY),g0,None,.5,4,25,4,7,1.5,0)
    flows.append(cv2.GaussianBlur(flow,(0,0),4))
r,g,b=ref[:,:,2].astype(float),ref[:,:,1].astype(float),ref[:,:,0].astype(float)
foliage=np.uint8((g>r*1.12)&(g>b*1.05)&(g-r>12))*255
foliage=cv2.dilate(foliage,np.ones((11,11),np.uint8));foliage=cv2.GaussianBlur(foliage,(0,0),3).astype(np.float32)/255
meta=json.loads((P/'grove-temple-retry-v2.json').read_text(encoding='utf-8'))['sections'][0]
for x,y,ww,hh in meta['excludeMapRects']:foliage[y:y+hh,x:x+ww]=0
y,x=np.mgrid[:h,:w].astype(np.float32);outputs=[];max_move=0
for i,flow in enumerate(flows):
    t=i/(len(flows)-1);ease=t*t*(3-2*t)
    # Correct the tiny endpoint mismatch, then retain only restrained motion.
    flow=(flow-ease*flows[-1])*.18
    magnitude=np.linalg.norm(flow,axis=2)
    flow*=np.minimum(1,2/np.maximum(magnitude,.0001))[:,:,None]*foliage[:,:,None]
    max_move=max(max_move,float(np.linalg.norm(flow,axis=2).max()))
    outputs.append(cv2.remap(ref,x+flow[:,:,0],y+flow[:,:,1],cv2.INTER_LINEAR,borderMode=cv2.BORDER_REFLECT_101))
outputs[0]=ref.copy();outputs[-1]=ref.copy()
out=P/'cedar-grove-restrained-v2.mp4'
proc=subprocess.Popen(['ffmpeg','-v','error','-y','-f','rawvideo','-pixel_format','bgr24','-video_size',f'{w}x{h}','-framerate',str(fps),'-i','-','-an','-c:v','libx264','-crf','18','-pix_fmt','yuv420p','-movflags','+faststart',str(out)],stdin=subprocess.PIPE)
for f in outputs:proc.stdin.write(f.tobytes())
proc.stdin.close()
if proc.wait():raise RuntimeError('Encoding failed')
report={'method':'Original input artwork sampled through a smoothed, attenuated optical-flow field from the registered Kling retry. Generated textures are not copied.','motionGuide':'cedar-grove-registered-v2.mp4','originalArt':'cedar-grove-input-v1.png','output':out.name,'maximumDisplacementPixels':max_move,'motionGain':.18,'endpointCorrection':'Subtract smoothstep-weighted final flow; first and last frames exactly original before encoding.','durationSeconds':len(outputs)/fps,'additionalCreditsSpent':0}
(P/'cedar-grove-restrained-v2.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
cv2.imwrite(str(P/'cedar-grove-restrained-frames-v2.jpg'),np.concatenate([outputs[0],outputs[len(outputs)//2],outputs[-1]],axis=1))
print(json.dumps(report,indent=2))
