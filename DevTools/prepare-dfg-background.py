"""Prepare a Kling courtyard video as a silent, web-ready looping MP4.

python DevTools/prepare-dfg-background.py DFGSim/assets/arena-courtyard-ambient-source.mp4
Requires ffmpeg and ffprobe on PATH. Preserves the source file and original timing.
"""
import argparse
import json
from pathlib import Path
import shutil
import subprocess
import tempfile


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('input', type=Path)
    parser.add_argument('--output', type=Path, default=Path(__file__).resolve().parents[1] / 'DFGSim/assets/arena-courtyard-ambient.mp4')
    parser.add_argument('--force', action='store_true')
    args = parser.parse_args()
    if not args.input.is_file():
        parser.error('Input video does not exist')
    if args.input.resolve() == args.output.resolve():
        parser.error('Input and output must be different files')
    if args.output.exists() and not args.force:
        parser.error('Output already exists; use --force to replace it')
    for executable in ('ffmpeg', 'ffprobe'):
        if not shutil.which(executable):
            parser.error(f'{executable} is required on PATH')
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile(dir=args.output.parent, suffix='.mp4', delete=False) as handle:
        temporary = Path(handle.name)
    try:
        subprocess.run([
            'ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', '-i', str(args.input),
            '-map', '0:v:0', '-an', '-vf', 'scale=min(1600\\,iw):-2',
            '-c:v', 'libx264', '-preset', 'medium', '-crf', '25', '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart', str(temporary),
        ], check=True)
        info = json.loads(subprocess.check_output([
            'ffprobe', '-v', 'error', '-show_streams', '-show_format', '-of', 'json', str(temporary),
        ], text=True))
        streams = info['streams']
        assert len(streams) == 1 and streams[0]['codec_type'] == 'video', 'Expected silent video only'
        video = streams[0]
        assert video['codec_name'] == 'h264' and video['pix_fmt'] == 'yuv420p', 'Expected browser-compatible H.264'
        assert float(info['format']['duration']) > 0, 'Video has no duration'
        temporary.replace(args.output)
        print(f"{args.output}: {video['width']}x{video['height']}, {info['format']['duration']} seconds, "
              f"H.264/yuv420p, no audio, {args.output.stat().st_size:,} bytes")
    finally:
        temporary.unlink(missing_ok=True)


if __name__ == '__main__':
    main()
