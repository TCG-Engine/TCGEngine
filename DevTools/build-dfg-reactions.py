"""Build transparent DFG reaction clips and their playback manifest.

python DevTools/build-dfg-reactions.py [--color blue|red] [--force]
Requires the downloaded fighter-<color>-hit-<kind>.mp4 source clips.
"""
import argparse
import importlib.util
import json
from pathlib import Path

from PIL import Image

spec = importlib.util.spec_from_file_location('fighter_converter', Path(__file__).with_name('convert-fighter-idle.py'))
converter = importlib.util.module_from_spec(spec)
spec.loader.exec_module(converter)
assets = Path(__file__).resolve().parents[1] / 'DFGSim' / 'assets'
kinds = ('light', 'medium', 'heavy', 'block', 'knockout')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--color', choices=('blue', 'red'))
    parser.add_argument('--kind', choices=kinds, help='Rebuild just one reaction kind')
    parser.add_argument('--force', action='store_true')
    args = parser.parse_args()
    for color in ((args.color,) if args.color else ('blue', 'red')):
        for kind in ((args.kind,) if args.kind else kinds):
            source = assets / f'fighter-{color}-hit-{kind}.mp4'
            converter.convert(source, source.with_suffix('.webp'), fps=12, height=480,
                              speed=1.5 if kind == 'knockout' else 2.5, loop_count=1, force=args.force)
    manifest = {}
    for color in ('blue', 'red'):
        for kind in kinds:
            path = assets / f'fighter-{color}-hit-{kind}.webp'
            if not path.is_file():
                continue
            with Image.open(path) as image:
                if image.info.get('loop') != 1:
                    raise ValueError(f'{path}: expected a single-play reaction')
                duration = 0
                for index in range(image.n_frames):
                    image.seek(index)
                    image.load()
                    duration += image.info['duration']
                if kind == 'knockout':
                    # Preserve the final defeated pose without replaying the fall.
                    image.convert('RGBA').save(path.with_name(path.stem + '-final.webp'), lossless=True)
            manifest.setdefault(color, {})[kind] = {
                'src': f'assets/{path.name}?v={path.stat().st_mtime_ns}',
                'durationMs': duration,
            }
            if kind == 'knockout':
                final = path.with_name(path.stem + '-final.webp')
                manifest[color][kind]['finalSrc'] = f'assets/{final.name}?v={final.stat().st_mtime_ns}'
    (assets / 'fighter-reactions.json').write_text(json.dumps(manifest, indent=2) + '\n', encoding='utf-8')


if __name__ == '__main__':
    main()
