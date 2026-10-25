"""Convert downloaded enemy clips using the existing player-character transparency pipeline."""
import argparse
import importlib.util
import json
from pathlib import Path
from PIL import Image

root = Path(__file__).resolve().parents[1]
assets = root / 'DFGSim' / 'roguelike' / 'assets'
spec = importlib.util.spec_from_file_location('fighter_converter', Path(__file__).with_name('convert-fighter-idle.py'))
converter = importlib.util.module_from_spec(spec)
spec.loader.exec_module(converter)

def trim_stray_fist(path, first=8, last=12, omit=None):
    """Omit a brief generated attacker; preserve total playback time."""
    frames, durations = [], []
    total = 0
    omitted = set(range(first, last + 1) if omit is None else omit)
    with Image.open(path) as image:
        for index in range(image.n_frames):
            image.seek(index)
            image.load()
            duration = image.info['duration']
            total += duration
            if index in omitted:
                continue
            frames.append(image.convert('RGBA'))
            durations.append(duration)
    # Rescale cumulative timestamps rather than changing the reaction's restore timing.
    retained = sum(durations)
    timestamps, elapsed = [0], 0
    for duration in durations:
        elapsed += duration
        timestamps.append(round(elapsed * total / retained))
    durations = [b-a for a,b in zip(timestamps,timestamps[1:])]
    temporary = path.with_suffix('.trim.webp')
    frames[0].save(temporary, format='WEBP', save_all=True, append_images=frames[1:],
                   duration=durations, loop=1, quality=90, method=4)
    temporary.replace(path)

def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--jobs', type=Path, default=assets / 'animation-jobs.json', help='Job manifest; output remains in the shared enemy animation folder')
    parser.add_argument('--enemy')
    parser.add_argument('--kind', choices=('idle', 'light', 'medium', 'heavy', 'block', 'knockout'))
    parser.add_argument('--force', action='store_true')
    args = parser.parse_args()
    jobs = json.loads(args.jobs.read_text(encoding='utf-8'))['jobs']
    selected = [j for j in jobs if (not args.enemy or j['enemy'] == args.enemy) and (not args.kind or j['kind'] == args.kind)]
    if not selected:
        parser.error('No jobs match the requested enemy/kind')
    folder = assets / 'animations'
    missing = [str(folder / j['source']) for j in selected if not (folder / j['source']).is_file()]
    if missing:
        raise SystemExit('Download these Kling source clips before building:\n' + '\n'.join(missing))
    manifest_path = folder / 'enemy-animations.json'
    manifest = json.loads(manifest_path.read_text(encoding='utf-8')) if manifest_path.is_file() else {}
    for job in selected:
        source = folder / job['source']
        target = folder / f'{job["id"]}.webp'
        settings = job['conversion']
        # These sprites' outlines are darker than the original players' outlines.
        converter.convert(source, target, fps=settings['fps'], height=settings['height'], threshold=4, feather=4,
                          speed=settings['speed'], loop_count=settings['loopCount'], force=args.force)
        if job['id'] == 'dragon-light':
            trim_stray_fist(target)
        if settings.get('omitSampledFrames'):
            trim_stray_fist(target, omit=settings['omitSampledFrames'])
        duration = 0
        with Image.open(target) as image:
            if image.info.get('loop') != settings['loopCount']:
                raise ValueError(f'{target}: wrong loop mode')
            for index in range(image.n_frames):
                image.seek(index)
                image.load()
                duration += image.info['duration']
            if job['kind'] == 'idle':
                image.seek(0)
                still = folder / f'{job["enemy"]}-still.webp'
                image.convert('RGBA').save(still, lossless=True)
                manifest.setdefault(job['enemy'], {})['still'] = 'roguelike/assets/animations/' + still.name + f'?v={still.stat().st_mtime_ns}'
            if job['kind'] == 'knockout':
                image.convert('RGBA').save(target.with_name(target.stem + '-final.webp'), lossless=True)
        prefix = 'roguelike/assets/animations/'
        entry = {'src': prefix + target.name + f'?v={target.stat().st_mtime_ns}', 'durationMs': duration}
        if job['kind'] == 'knockout':
            final = target.with_name(target.stem + '-final.webp')
            entry['finalSrc'] = prefix + final.name + f'?v={final.stat().st_mtime_ns}'
        manifest.setdefault(job['enemy'], {})[job['kind']] = entry
    temporary = manifest_path.with_suffix('.json.tmp')
    temporary.write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')
    temporary.replace(manifest_path)
    print(f'Built {len(selected)} clips and {manifest_path}')

if __name__ == '__main__':
    main()
