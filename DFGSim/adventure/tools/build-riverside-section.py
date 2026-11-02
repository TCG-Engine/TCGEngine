"""Build the study matte from existing Kling footage; does not submit jobs."""
from pathlib import Path
import json
import cv2
import numpy as np

ASSETS = Path(__file__).resolve().parents[1] / 'assets' / 'ambient-pilot'
reference = cv2.imread(str(ASSETS / 'riverside-water-input-v1.png'))
cap = cv2.VideoCapture(str(ASSETS / 'riverside-water-registered-v1.mp4'))
frames = []
while True:
    ok, frame = cap.read()
    if not ok:
        break
    frames.append(frame)
cap.release()
stack = np.stack(frames)
# One connected river section below the pier. Keep complete motion envelopes,
# including the background revealed when a reed bends, rather than leaf cutouts.
mask = np.zeros((672, 480), np.uint8)
polygon = np.array([[75, 425], [175, 417], [213, 360], [370, 360],
                    [385, 370], [479, 370], [479, 499], [175, 499],
                    [100, 493], [75, 465]], np.int32)
cv2.fillPoly(mask, [polygon], 255)
mask = cv2.GaussianBlur(mask, (17, 17), 0)
# Hard perimeter exclusions: no pixel of the pier/posts or flashing corner.
mask[:365] = 0
mask[508:] = 0
cv2.imwrite(str(ASSETS / 'riverside-section-mask-v1.png'), mask)
alpha = mask[:, :, None].astype(np.float32) / 255
composites = np.rint(reference[None] * (1-alpha) + stack * alpha).astype(np.uint8)
protected = mask == 0
outside = int(np.abs(composites.astype(np.int16)-reference[None].astype(np.int16))[:, protected].max())
motion = stack.astype(np.float32).std(axis=0).mean(axis=2)
# Check the full swept envelopes, not just silhouettes in the first image.
checks = {}
for name, (x, y, w, h) in {'mainReeds': (220, 360, 160, 132), 'bankReeds': (85, 420, 90, 78)}.items():
    moving = motion[y:y+h, x:x+w] > 8
    coverage = mask[y:y+h, x:x+w][moving]
    checks[name] = {'movingPixels': int(moving.sum()), 'minimumMotionOpacity': int(coverage.min()) if coverage.size else 0}
report = {'sourceVideo': 'riverside-water-registered-v1.mp4',
          'sourceMapRect': [1056, 288, 480, 672], 'mask': 'riverside-section-mask-v1.png',
          'maskNonzeroPixels': int((mask > 0).sum()), 'outsideMaskMaxChannelDifference': outside,
          'pierAndPostsMaskMax': int(mask[:365].max()),
          'flashingLowerCornerMaskMax': int(mask[508:].max()), 'reedMotionCoverage': checks,
          'scope': 'Existing wide take, coherent below-pier reeds and river section. Water in this take remains mostly static.',
          'creditsSpentThisPass': 0}
(ASSETS / 'section-review.json').write_text(json.dumps(report, indent=2)+'\n', encoding='utf-8')
cv2.imwrite(str(ASSETS / 'riverside-section-review.jpg'), np.concatenate([composites[0], composites[60], composites[100]], axis=1))
print(json.dumps(report, indent=2))
