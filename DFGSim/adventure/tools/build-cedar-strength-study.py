"""Compare continuous, locally authored cedar breezes using original artwork."""
from pathlib import Path
import cv2, numpy as np, json, subprocess

P=Path(__file__).resolve().parents[1]/'assets'/'ambient-sections'
ref=cv2.imread(str(P/'cedar-grove-input-v1.png'));h,w=ref.shape[:2]
fps=24
frame_count=193  # Eight seconds plus a matching endpoint.
r,g,b=ref[:,:,2].astype(float),ref[:,:,1].astype(float),ref[:,:,0].astype(float)
green=np.uint8((g>r*1.12)&(g>b*1.05)&(g-r>12))*255
foliage=cv2.GaussianBlur(cv2.dilate(green,np.ones((11,11),np.uint8)),(0,0),3).astype(np.float32)/255
# Favor the drawn branch boundaries; broad foliage interiors receive less motion.
edges=cv2.Canny(cv2.cvtColor(ref,cv2.COLOR_BGR2GRAY),70,140)
edges=cv2.bitwise_and(edges,cv2.dilate(green,np.ones((9,9),np.uint8)))
distance=cv2.distanceTransform(255-edges,cv2.DIST_L2,5)
branch_weight=.12+.88*np.exp(-distance/6)
weight=foliage*branch_weight
meta=json.loads((P/'grove-temple-retry-v2.json').read_text(encoding='utf-8'))['sections'][0]
for xx,yy,ww,hh in meta['excludeMapRects']:weight[yy:yy+hh,xx:xx+ww]=0
# Fixed trunk bases, extending below each tree's lowest branch tier.
trunks=[[59,188,29,46],[169,156,30,30],[260,153,30,34],[417,185,32,48]]
protected=np.zeros((h,w),np.uint8)
for xx,yy,ww,hh in trunks:protected[yy:yy+hh,xx:xx+ww]=255
weight[protected>0]=0
cv2.imwrite(str(P/'cedar-strength-motion-region-v4.png'),np.uint8(weight*255))
y,x=np.mgrid[:h,:w].astype(np.float32)
# Multiple periodic components, spatially staggered across branches and trees.
# Unlike replaying the Kling gust, these have no held/static part of the cycle.
phase=x/85+y/95
fields=[]
for i in range(frame_count):
    theta=2*np.pi*i/(frame_count-1)
    dx=.78*np.sin(2*theta+phase)+.22*np.sin(3*theta-phase*.7)
    dy=.17*np.sin(3*theta+phase*.8)+.07*np.cos(2*theta-phase)
    fields.append(np.stack((dx,dy),axis=2)*weight[:,:,None])
fields[-1]=fields[0].copy()  # Avoid floating-point seam quantization.
normalizer=max(float(np.linalg.norm(f,axis=2).max()) for f in fields)
regions=[(25,55,110,140),(125,20,90,130),(225,20,100,130),(375,20,110,165)]

variants=[]
for name,limit,label in [('light',4,'Light breeze'),('medium',7,'Clear breeze'),('strong',10,'Strong breeze')]:
    filename=f'cedar-grove-{name}-v4.mp4'
    proc=subprocess.Popen(['ffmpeg','-v','error','-y','-f','rawvideo','-pixel_format','bgr24','-video_size',f'{w}x{h}','-framerate',str(fps),'-i','-','-an','-c:v','libx264','-crf','17','-pix_fmt','yuv420p','-movflags','+faststart',str(P/filename)],stdin=subprocess.PIPE)
    maximum=0;motion_sum=0;moving_count=0;fixed_error=0;previous=None;largest_step=0;samples=[]
    rendered=[]
    for i,field in enumerate(fields):
        displacement=field*(limit/normalizer)
        magnitude=np.linalg.norm(displacement,axis=2)
        displacement*=np.minimum(1,limit/np.maximum(magnitude,.0001))[:,:,None]
        magnitude=np.linalg.norm(displacement,axis=2)
        maximum=max(maximum,float(magnitude.max()))
        motion_sum+=float(magnitude[weight>.1].sum());moving_count+=int((weight>.1).sum())
        output=cv2.remap(ref,x+displacement[:,:,0],y+displacement[:,:,1],cv2.INTER_LINEAR,borderMode=cv2.BORDER_REFLECT_101)
        rendered.append(output)
        fixed_error=max(fixed_error,int(np.abs(output.astype(np.int16)-ref.astype(np.int16))[protected>0].max()))
        if previous is not None:largest_step=max(largest_step,float(np.abs(output.astype(float)-previous.astype(float)).mean()))
        previous=output
        if i in [0,30,60,90]:samples.append(output)
        proc.stdin.write(output.tobytes())
    proc.stdin.close()
    if proc.wait():raise RuntimeError('Encoding failed')
    cv2.imwrite(str(P/f'cedar-grove-{name}-frames-v4.jpg'),np.concatenate(samples,axis=1))
    # Measure every half-second window, including across the loop seam, on
    # original frames (not compression noise) and each of four main canopies.
    unique=rendered[:-1];steps=[];regional=[]
    for i in range(len(unique)):
        delta=np.abs(unique[(i+12)%len(unique)].astype(float)-unique[i].astype(float)).mean(axis=2)
        steps.append(float(delta[weight>.1].mean()))
        regional.append([float(delta[yy:yy+hh,xx:xx+ww][weight[yy:yy+hh,xx:xx+ww]>.1].mean()) for xx,yy,ww,hh in regions])
    activity={'windowSeconds':.5,'minimumMeanFoliageChannelChange':min(steps),'medianMeanFoliageChannelChange':float(np.median(steps)),'minimumPerCanopyChannelChange':np.min(regional,axis=0).tolist(),'matchingEndpointsMaxChannelDifference':int(np.abs(rendered[0].astype(int)-rendered[-1].astype(int)).max())}
    assert activity['minimumMeanFoliageChannelChange']>1
    assert min(activity['minimumPerCanopyChannelChange'])>.3
    assert activity['matchingEndpointsMaxChannelDifference']==0

    variants.append({'id':name,'label':label,'maximumDisplacementPixels':maximum,'limitPixels':limit,'meanFoliageDisplacementPixels':motion_sum/moving_count,'protectedTrunkMaxChannelDifferenceBeforeEncoding':fixed_error,'maximumMeanFrameStep':largest_step,'video':filename,'durationSeconds':frame_count/fps,'continuousActivity':activity,'playbackRate':.91})
    print(name,json.dumps(variants[-1]))
report={'status':'study_only','originalArt':'cedar-grove-input-v1.png','motionGuide':None,'method':'Locally authored overlapping periodic breeze fields with spatially staggered phases. Every frame samples only original artwork. No Kling gust timeline or generated textures are used. Branch edges move more than broad interiors. Fixed trunk bases and cropped objects are protected.','protectedTrunkRects':trunks,'endpointCheck':'First and last frames exactly match each other before encoding; periodic motion continues through the seam. No crossfade or hold.','additionalCreditsSpent':0,'variants':variants}
(P/'cedar-strength-study-v4.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
