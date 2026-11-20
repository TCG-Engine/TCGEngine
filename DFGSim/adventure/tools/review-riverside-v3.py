"""Register and measure the approved full-section Kling 4 take, without new jobs."""
from pathlib import Path
import cv2, numpy as np, json, subprocess
P = Path(__file__).resolve().parents[1] / 'assets' / 'ambient-pilot'
ref=cv2.imread(str(P/'riverside-water-input-v1.png'))
static=np.zeros((672,480),np.uint8);static[:410,:220]=255
orb=cv2.ORB_create(nfeatures=2500)
kr,dr=orb.detectAndCompute(cv2.cvtColor(ref,cv2.COLOR_BGR2GRAY),static)
cap=cv2.VideoCapture(str(P/'riverside-section-source-v3.mp4'))
fps=cap.get(cv2.CAP_PROP_FPS);frames=[];errors=[]
while True:
    ok,frame=cap.read()
    if not ok:break
    frame=cv2.resize(frame,(480,672),interpolation=cv2.INTER_AREA)
    k,d=orb.detectAndCompute(cv2.cvtColor(frame,cv2.COLOR_BGR2GRAY),static)
    matches=cv2.BFMatcher(cv2.NORM_HAMMING).knnMatch(d,dr,k=2)
    good=[a for a,b in matches if a.distance<.72*b.distance]
    src=np.float32([k[m.queryIdx].pt for m in good]);dst=np.float32([kr[m.trainIdx].pt for m in good])
    mat,inliers=cv2.estimateAffinePartial2D(src,dst,method=cv2.RANSAC,ransacReprojThreshold=2)
    if mat is None:raise RuntimeError('Cannot register frame')
    frames.append(cv2.warpAffine(frame,mat,(480,672),flags=cv2.INTER_LINEAR,borderMode=cv2.BORDER_REFLECT_101))
    errors.append(float(np.median(np.linalg.norm(cv2.transform(src[None],mat)[0][inliers[:,0]>0]-dst[inliers[:,0]>0],axis=1))))
cap.release()
proc=subprocess.Popen(['ffmpeg','-v','error','-y','-f','rawvideo','-pixel_format','bgr24','-video_size','480x672','-framerate',str(fps),'-i','-','-an','-c:v','libx264','-crf','18','-pix_fmt','yuv420p','-movflags','+faststart',str(P/'riverside-section-registered-v3.mp4')],stdin=subprocess.PIPE)
for frame in frames:proc.stdin.write(frame.tobytes())
proc.stdin.close()
if proc.wait():raise RuntimeError('encode failed')
stack=np.stack(frames).astype(np.float32);motion=stack.std(axis=0).mean(axis=2)
regions={}
for name,(x,y,w,h) in {'upperWater':(425,100,55,90),'belowPierWater':(375,370,95,100),'upperReeds':(270,135,95,70),'middleReeds':(240,370,120,110),'lowerReeds':(100,530,75,85),'lowerRight':(420,550,60,120)}.items():
    crop=stack[:,y:y+h,x:x+w]
    regions[name]={'meanTemporalPixelDeviation':float(motion[y:y+h,x:x+w].mean()),'maximumMeanBrightnessJump':float(np.abs(np.diff(crop.mean(axis=(1,2,3)))).max())}
report={'frames':len(frames),'fps':fps,'durationSeconds':len(frames)/fps,'registrationMedianErrorPixels':float(np.median(errors)),'firstLastMeanChannelDifference':float(np.abs(stack[0]-stack[-1]).mean()),'meanAdjacentFrameDifference':float(np.abs(np.diff(stack,axis=0)).mean()),'regions':regions,'endBlendApplied':False,'rawRetained':True}
(P/'riverside-section-v3-review.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
cv2.imwrite(str(P/'riverside-section-v3-frames.jpg'),np.concatenate([frames[0],frames[len(frames)//2],frames[-1]],axis=1))
cv2.imwrite(str(P/'riverside-section-v3-motion.png'),cv2.applyColorMap(np.uint8(np.clip(motion*8,0,255)),cv2.COLORMAP_INFERNO))
print(json.dumps(report,indent=2))
