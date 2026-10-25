"""Validate delivered enemy media, including every encoded frame and held poses."""
import hashlib
import json
import argparse
from pathlib import Path
from PIL import Image

root = Path(__file__).resolve().parents[1]
folder = root / 'DFGSim/roguelike/assets/animations'
manifest = json.loads((folder / 'enemy-animations.json').read_text(encoding='utf-8'))
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--jobs', type=Path, default=folder.parent / 'animation-jobs.json')
args = parser.parse_args()
jobs = json.loads(args.jobs.read_text(encoding='utf-8'))['jobs']
hashes, frame_count = set(), 0

def visible(image, background):
    rgba = image.convert('RGBA')
    return Image.alpha_composite(Image.new('RGBA', rgba.size, background), rgba).tobytes()

for job in jobs:
    source = folder / job['source']
    digest = hashlib.sha256(source.read_bytes()).hexdigest()
    assert digest not in hashes, f'Duplicate source: {source}'
    hashes.add(digest)
    entry = manifest[job['enemy']][job['kind']]
    with Image.open(folder / f'{job["id"]}.webp') as clip:
        assert clip.height == 480
        assert clip.info['loop'] == job['conversion']['loopCount']
        duration = 0
        for index in range(clip.n_frames):
            clip.seek(index)
            clip.load()
            assert clip.convert('RGBA').getchannel('A').getextrema() == (0, 255), (job['id'], index)
            assert clip.info['duration'] > 0
            duration += clip.info['duration']
            frame_count += 1
        assert duration == entry['durationMs'], job['id']
        if job['kind'] in ('idle', 'knockout'):
            if job['kind'] == 'idle':
                clip.seek(0)
                held = folder / f'{job["enemy"]}-still.webp'
            else:
                held = folder / f'{job["id"]}-final.webp'
            with Image.open(held) as still:
                for background in ('black', 'white'):
                    assert visible(clip, background) == visible(still, background), held

assert jobs, 'Empty job manifest'
print(f'PASS {len(jobs)} unique sources, {frame_count} transparent frames, timing, loops and held poses')
