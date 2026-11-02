"""Loop an active interior cycle of the actual Kling take; no image warping."""
from pathlib import Path
import json, subprocess
import cv2, numpy as np

root=Path(__file__).resolve().parents[1]/'assets'/'ambient-sections'
source=root/'cedar-grove-registered-v5.mp4'
first,stop=42,88  # One interior cycle, inclusive first / exclusive stop.
rate=.4
output=root/'cedar-grove-active-loop-v6.mp4'
subprocess.run(['ffmpeg','-v','error','-y','-i',str(source),'-vf',f'trim=start_frame={first}:end_frame={stop},setpts=PTS-STARTPTS','-an','-c:v','libx264','-crf','17','-pix_fmt','yuv420p','-movflags','+faststart',str(output)],check=True)
cap=cv2.VideoCapture(str(output));fps=cap.get(cv2.CAP_PROP_FPS);frames=[]
while True:
    ok,frame=cap.read()
    if not ok:break
    frames.append(frame.astype(np.float32))
cap.release()
assert len(frames)==stop-first
mask=cv2.imread(str(root/'cedar-grove-mask-v5.png'),cv2.IMREAD_GRAYSCALE)>0
steps=[float(np.abs(b-a)[mask].mean()) for a,b in zip(frames,frames[1:])]
seam=float(np.abs(frames[0]-frames[-1])[mask].mean())
assert seam<max(steps), 'Loop join must be smaller than the largest normal frame step'
report={
    'status':'study_only','source':source.name,'video':output.name,
    'sourceFramesInclusive':[first,stop-1],'sourceTimeRangeSeconds':[first/fps,stop/fps],
    'fps':fps,'frames':len(frames),'durationSeconds':len(frames)/fps,
    'defaultPlaybackRate':rate,'effectiveLoopSeconds':len(frames)/fps/rate,
    'method':'Forward-only trim of an active interior cycle. Browser plays at 40% speed. No image warp, reverse playback, crossfade or generated frames.',
    'seamMeanChannelDifferenceInsideMask':seam,
    'maximumNormalStepInsideMask':max(steps),
    'meanNormalStepInsideMask':float(np.mean(steps)),
    'additionalCreditsSpent':0,'liveGameChanged':False,
}
(root/'cedar-active-loop-v6.json').write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print(json.dumps(report,indent=2))
