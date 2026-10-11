"""Run a predeclared experiment phase with isolated PHP processes and bounded concurrency."""
import argparse
from concurrent.futures import ThreadPoolExecutor, wait, FIRST_COMPLETED
import json
import os
from pathlib import Path
import shutil
import subprocess


def run_variant(root, directory, phase, spec, variant):
    path = directory / (phase + '-' + variant + '.json')
    if path.exists():
        data = json.loads(path.read_text())
        if any(data.get(key, 'sinistcha' if key == 'base' else None) != value for key, value in [('variant', variant), ('seed', spec['seed']),
               ('pairs', spec['pairs']), ('opponent', spec['opponent']), ('base', spec.get('base','sinistcha'))]):
            raise ValueError('Existing output does not match the plan: ' + str(path))
        if data.get('suite','default') != spec.get('suite','default'):
            raise ValueError('Existing output does not match the suite: ' + str(path))
        return data
    command = [shutil.which('php'), 'DevTools/PokeSim/deck-experiment.php', '--variant=' + variant,
               '--base=' + spec.get('base', 'sinistcha'), '--seed=' + str(spec['seed']), '--pairs=' + str(spec['pairs']),
               '--opponent=' + spec['opponent'], '--output=' + str(path)]
    command.append('--suite=' + spec.get('suite','default'))
    process = subprocess.run(command, cwd=root, capture_output=True, text=True,
                             creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0)
    if process.returncode:
        raise RuntimeError(variant + ': ' + process.stderr)
    return json.loads(path.read_text())


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('directory', type=Path)
    parser.add_argument('--phase', required=True)
    parser.add_argument('--workers', type=int, default=6)
    args = parser.parse_args()
    if not 1 <= args.workers <= 8:
        parser.error('Use 1–8 workers')
    root = Path(__file__).resolve().parents[2]
    directory = args.directory.resolve()
    plan = json.loads((directory / 'plan.json').read_text())
    spec = plan['phases'][args.phase]
    with ThreadPoolExecutor(max_workers=args.workers) as pool:
        pending = {pool.submit(run_variant, root, directory, args.phase, spec, variant): variant
                   for variant in spec['variants']}
        total = len(pending)
        while pending:
            done, _ = wait(pending, timeout=30, return_when=FIRST_COMPLETED)
            if not done:
                print(f'{args.phase}: {total-len(pending)}/{total} variants finished', flush=True)
            for future in done:
                variant = pending.pop(future)
                data = future.result()
                summary = data['summary']
                openings = [opening for game in data['results'] if game['status'] == 'complete'
                            for opening in game['openingStats'] if opening['player'] == 1
                            and opening['order'] == 'second' and opening['status'] == 'complete']
                enabled = sum(o['fullyEnabledAttack'] for o in openings) / len(openings) if openings else 0
                win = (summary['seat1FirstWins'] + summary['seat1SecondWins']) / summary['completed']
                print(f'{args.phase}: {total-len(pending)}/{total} {variant}: win {win:.1%}, second boosted {enabled:.1%}', flush=True)
