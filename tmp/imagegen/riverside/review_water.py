from pathlib import Path
import cv2, numpy as np, json, subprocess
from PIL import Image, ImageDraw
root=Path('C:/xampp/htdocs/TCGEngine')
assets=root/'DFGSim/adventure/assets/ambient-pilot'
ref=cv2.imread(str(assets/'riverside-water-input-v1.png'))
mask=cv2.imread(str(assets/'riverside-water-mask-v1.png'),0)
land=np.uint8(mask==0)*255
orb=cv2.ORB_create(nfeatures=2500)
kr,dr=orb.detectAndCompute(cv2.cvtColor(ref,cv2.COLOR_BGR2GRAY),land)
cap=cv2.VideoCapture(str(assets/'riverside-water-source-v1.mp4'))
fps=cap.get(cv2.CAP_PROP_FPS)
frames=[]; matrices=[]; errors=[]
while True:
 ok,frame=cap.read()
 if not ok: break
 frame=cv2.resize(frame,(480,672),interpolation=cv2.INTER_AREA)
 k,d=orb.detectAndCompute(cv2.cvtColor(frame,cv2.COLOR_BGR2GRAY),land)
 pairs=cv2.BFMatcher(cv2.NORM_HAMMING).knnMatch(d,dr,k=2)
 good=[a for a,b in pairs if a.distance<.72*b.distance]
 src=np.float32([k[m.queryIdx].pt for m in good]); dst=np.float32([kr[m.trainIdx].pt for m in good])
 mat,inliers=cv2.estimateAffinePartial2D(src,dst,method=cv2.RANSAC,ransacReprojThreshold=2)
 if mat is None: raise RuntimeError('Cannot register frame')
 reg=cv2.warpAffine(frame,mat,(480,672),flags=cv2.INTER_LINEAR,borderMode=cv2.BORDER_REFLECT_101)
 frames.append(reg);matrices.append(mat.tolist())
 errors.append(float(np.median(np.linalg.norm(cv2.transform(src[None],mat)[0][inliers[:,0]>0]-dst[inliers[:,0]>0],axis=1))))
cap.release()
# A short wrap blend hides codec/end-keyframe differences, only within the eventual water mask.
blend=8
for j in range(blend):
 t=(j+1)/blend
 frames[-blend+j]=cv2.addWeighted(frames[-blend+j],1-t,frames[0],t,0)
out=assets/'riverside-water-registered-v1.mp4'
proc=subprocess.Popen(['ffmpeg','-v','error','-y','-f','rawvideo','-pixel_format','bgr24','-video_size','480x672','-framerate',str(fps),'-i','-','-an','-c:v','libx264','-crf','18','-pix_fmt','yuv420p','-movflags','+faststart',str(out)],stdin=subprocess.PIPE)
for frame in frames: proc.stdin.write(frame.tobytes())
proc.stdin.close()
if proc.wait(): raise RuntimeError('encode failed')
alpha=mask[:,:,None].astype(np.float32)/255
composites=[np.rint(ref*(1-alpha)+f*alpha).astype(np.uint8) for f in frames]
outside=max(int(np.abs(c.astype(int)-ref.astype(int))[mask==0].max()) for c in composites)
motion=[float(np.abs(frames[i].astype(float)-frames[i-1].astype(float))[mask>180].mean()) for i in range(1,len(frames))]
report={'frames':len(frames),'fps':fps,'durationSeconds':len(frames)/fps,'registrationMedianErrorPixels':float(np.median(errors)),'registrationWorstMedianErrorPixels':max(errors),'outsideMaskMaxChannelDifference':outside,'meanWaterFrameDifference':float(np.mean(motion)),'loopEndDifference':float(np.abs(frames[-1].astype(float)-frames[0].astype(float)).mean()),'loopBlendFrames':blend,'registration':'per-frame ORB/RANSAC similarity transform to fixed input land','output':'riverside-water-registered-v1.mp4','transforms':matrices}
(assets/'review.json').write_text(json.dumps(report,indent=2),encoding='utf-8')
sheet=Image.new('RGB',(480*3,720),'#f4e8cf');draw=ImageDraw.Draw(sheet)
for n,i in enumerate([0,len(frames)//2,len(frames)-9]):
 sheet.paste(Image.fromarray(cv2.cvtColor(composites[i],cv2.COLOR_BGR2RGB)),(480*n,30));draw.text((480*n+10,10),f'Masked composite {i/fps:.2f}s',fill='black')
sheet.save(root/'tmp/imagegen/riverside/water-review.jpg')
print(json.dumps({k:v for k,v in report.items() if k!='transforms'},indent=2))
