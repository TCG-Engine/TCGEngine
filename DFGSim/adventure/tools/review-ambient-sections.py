"""Register the two approved section clips and preserve their unmodified outputs."""
from pathlib import Path
import json, sys, hashlib, subprocess
import cv2, numpy as np
P=Path(__file__).resolve().parents[1]/'assets'/'ambient-sections'
manifest_path=P/(sys.argv[2] if len(sys.argv)>2 else 'prepared.json')
manifest=json.loads(manifest_path.read_text(encoding='utf-8'))
for section in manifest['sections']:
    slug=section['id']
    if len(sys.argv)>1 and slug!=sys.argv[1]:continue
    version='v'+str(section.get('version',1))
    source=slug+'-source-'+version+'.mp4'
    ref=cv2.imread(str(P/section['input']));h,w=ref.shape[:2]
    static=None
    if slug=='cedar-grove':
        static=np.zeros((h,w),np.uint8);static[180:350,150:]=255
    orb=cv2.ORB_create(nfeatures=3000);kr,dr=orb.detectAndCompute(cv2.cvtColor(ref,cv2.COLOR_BGR2GRAY),static)
    cap=cv2.VideoCapture(str(P/source));fps=cap.get(cv2.CAP_PROP_FPS);frames=[];errors=[]
    while True:
        ok,frame=cap.read()
        if not ok:break
        frame=cv2.resize(frame,(w,h),interpolation=cv2.INTER_AREA)
        k,d=orb.detectAndCompute(cv2.cvtColor(frame,cv2.COLOR_BGR2GRAY),static)
        pairs=cv2.BFMatcher(cv2.NORM_HAMMING).knnMatch(d,dr,k=2);good=[a for a,b in pairs if a.distance<.7*b.distance]
        src=np.float32([k[m.queryIdx].pt for m in good]);dst=np.float32([kr[m.trainIdx].pt for m in good])
        mat,inliers=cv2.estimateAffinePartial2D(src,dst,method=cv2.RANSAC,ransacReprojThreshold=2)
        if mat is None:raise RuntimeError('Cannot register '+slug)
        frames.append(cv2.warpAffine(frame,mat,(w,h),flags=cv2.INTER_LINEAR,borderMode=cv2.BORDER_REFLECT_101))
        errors.append(float(np.median(np.linalg.norm(cv2.transform(src[None],mat)[0][inliers[:,0]>0]-dst[inliers[:,0]>0],axis=1))))
    cap.release()
    if not frames:raise RuntimeError('No frames in '+slug)
    video=slug+'-registered-'+version+'.mp4'
    proc=subprocess.Popen(['ffmpeg','-v','error','-y','-f','rawvideo','-pixel_format','bgr24','-video_size',f'{w}x{h}','-framerate',str(fps),'-i','-','-an','-c:v','libx264','-crf','18','-pix_fmt','yuv420p','-movflags','+faststart',str(P/video)],stdin=subprocess.PIPE)
    for f in frames:proc.stdin.write(f.tobytes())
    proc.stdin.close()
    if proc.wait():raise RuntimeError('Encode failed')
    y,x=np.mgrid[:h,:w];mask=np.uint8(np.clip(np.minimum.reduce([x,y,w-1-x,h-1-y])/16,0,1)*255)
    # Keep original bridge planks/posts; admit surrounding ripples and reeds.
    if slug=='courtyard-canals':
        r,g,b=ref[:,:,2].astype(float),ref[:,:,1].astype(float),ref[:,:,0].astype(float)
        brown=(r>g*1.04)&(g>b*1.12);roi=np.zeros((h,w),np.uint8);roi[110:285,250:390]=255
        protected=cv2.dilate(np.uint8(brown&(roi>0)),np.ones((3,3),np.uint8))>0;mask[protected]=0
        # These temple decorations enter from outside the input crop. Their
        # complete silhouettes are not available, so preserve this whole area.
        mask[:110,520:]=0
    if slug=='cedar-grove':
        # The top and left are actual world edges, with no adjacent static art.
        mask=np.uint8(np.clip(np.minimum(w-1-x,h-1-y)/16,0,1)*255)
    sx,sy,_,_=section['rect']
    for mx,my,mw,mh in section.get('excludeMapRects',[]):
        x1,y1=max(0,mx-sx),max(0,my-sy);x2,y2=min(w,mx+mw-sx),min(h,my+mh-sy)
        if x2>x1 and y2>y1:mask[y1:y2,x1:x2]=0
    maskFile=slug+'-mask-'+version+'.png';cv2.imwrite(str(P/maskFile),mask)
    stack=np.stack(frames).astype(np.float32);alpha=mask[:,:,None].astype(np.float32)/255
    composites=np.rint(ref[None]*(1-alpha)+stack*alpha).astype(np.uint8)
    outside=int(np.abs(composites.astype(np.int16)-ref[None].astype(np.int16))[:,mask==0].max())
    motion=stack.std(axis=0).mean(axis=2)
    cv2.imwrite(str(P/(slug+'-motion-'+version+'.png')),cv2.applyColorMap(np.uint8(np.clip(motion*8,0,255)),cv2.COLORMAP_INFERNO))
    cv2.imwrite(str(P/(slug+'-frames-'+version+'.jpg')),np.concatenate([frames[0],frames[len(frames)//2],frames[-1]],axis=1))
    report={'frames':len(frames),'fps':fps,'durationSeconds':len(frames)/fps,'effectiveLoopSeconds':len(frames)/fps/section['playbackRate'],'registrationMedianErrorPixels':float(np.median(errors)),'registrationWorstMedianErrorPixels':max(errors),'firstLastMeanChannelDifference':float(np.abs(stack[0]-stack[-1]).mean()),'meanAdjacentFrameDifference':float(np.abs(np.diff(stack,axis=0)).mean()),'meanTemporalPixelDeviation':float(motion.mean()),'maxMeanBrightnessJump':float(np.abs(np.diff(stack.mean(axis=(1,2,3)))).max()),'outsideMaskMaxChannelDifference':outside,'endBlendApplied':False}
    (P/(slug+'-review-'+version+'.json')).write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
    section.update(status='generated_under_review',sourceVideo=source,registeredVideo=video,mask=maskFile,review=slug+'-review-'+version+'.json',quotedCredits=50,sha256=hashlib.sha256((P/source).read_bytes()).hexdigest())
    print(slug,json.dumps(report))
manifest_path.write_text(json.dumps(manifest,indent=2)+'\n',encoding='utf-8')
