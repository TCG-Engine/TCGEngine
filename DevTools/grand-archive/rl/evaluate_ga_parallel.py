"""Frozen GA evaluation with isolated PHP workers and content-addressed caching."""
import argparse
from concurrent.futures import ThreadPoolExecutor
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[3]


def digest(path):
    return hashlib.sha256(Path(path).read_bytes()).hexdigest()


def identity(config, baseline, checkpoint, games, seed, max_steps):
    config, baseline, checkpoint = map(lambda p: Path(p).resolve(), (config, baseline, checkpoint))
    files = {config, checkpoint}
    files.update(p for p in baseline.rglob('*') if p.is_file())
    for deck in json.loads(config.read_text())['decks']:
        files.add((config.parent / deck['path']).resolve())
    # Conservative invalidation includes shared engine, generated card code,
    # per-game helpers, and the evaluation implementation itself.
    for folder in ('Core', 'GrandArchiveSim', 'DevTools/rl', 'DevTools/grand-archive', 'Schemas/GrandArchiveSim'):
        for directory, children, names in os.walk(ROOT / folder):
            children[:] = [name for name in children if name not in ('Games', 'artifacts', '__pycache__')]
            files.update((Path(directory) / name).resolve() for name in names
                         if Path(name).suffix in ('.php', '.py', '.txt'))
    files.add(ROOT / 'DevTools/TestAutomationBridge.php')
    files.update(ROOT.glob('*.php'))
    return {'games': games, 'seed': seed, 'maxSteps': max_steps,
            'phpVersion': subprocess.check_output(['php', '--version'], text=True),
            'files': {str(p): digest(p) for p in sorted(files)}}


def cache_valid(folder, expected):
    try:
        manifest = json.loads((folder / 'cache.json').read_text())
        return (manifest['identity'] == expected and bool(manifest['outputs'])
                and all(digest(folder / name) == sha for name, sha in manifest['outputs'].items()))
    except (OSError, ValueError, KeyError):
        return False


def evaluate(config, baseline, checkpoint, output, games=42, seed=9000001,
             max_steps=1000, workers=2, cache=None):
    if games < 2 or games % 2 or workers < 1 or max_steps < 1:
        raise ValueError('Use an even positive game count and positive workers/max steps.')
    output = Path(output).resolve()
    if output.exists():
        raise ValueError(f'Output already exists: {output}')
    started = time.monotonic()
    expected = identity(config, baseline, checkpoint, games, seed, max_steps)
    key = hashlib.sha256(json.dumps(expected, sort_keys=True).encode()).hexdigest()
    entry = Path(cache).resolve() / key if cache else None
    hit = entry is not None and cache_valid(entry, expected)
    if hit:
        shutil.copytree(entry, output)
    else:
        output.mkdir(parents=True)
        count = min(workers, games // 2)
        pairs, remainder = divmod(games // 2, count)
        chunks, start = [], 0
        for worker in range(count):
            size = 2 * (pairs + (worker < remainder))
            chunks.append((start, size, output / f'worker-{worker}'))
            start += size

        def run(chunk):
            start, size, folder = chunk
            command = ['php', str(ROOT / 'DevTools/grand-archive/rl/train_ga_tournament.php'),
                       '--config', str(Path(config).resolve()), '--baseline', str(Path(baseline).resolve()),
                       '--checkpoint', str(Path(checkpoint).resolve()), '--output', str(folder),
                       '--start', str(start), '--games', str(size), '--seed', str(seed),
                       '--max-steps', str(max_steps), '--evaluate']
            with (output / (folder.name + '.log')).open('w') as log:
                subprocess.run(command, cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, check=True)
            return json.loads((folder / 'report.json').read_text())

        with ThreadPoolExecutor(max_workers=count) as pool:
            reports = list(pool.map(run, chunks))
        merged = dict(reports[0])
        merged['start'], merged['games'] = 0, []
        for report, (start, size, folder) in zip(reports, chunks):
            for field in ('mode', 'seed', 'deckHashes', 'baseline', 'sourceHashes', 'maxSteps',
                          'initialPolicyHash', 'finalPolicyHash'):
                if report[field] != merged[field]:
                    raise ValueError(f'Worker conditions differ: {field}')
            if report['initialPolicyHash'] != report['finalPolicyHash']:
                raise ValueError('Evaluation changed policy.')
            if [g['episode'] for g in report['games']] != list(range(start + 1, start + size + 1)):
                raise ValueError('Worker schedule incomplete or reordered.')
            for game in report['games']:
                if not game.get('initialGamestateHash'):
                    raise ValueError('Missing starting-state hash.')
                shutil.copy2(folder / f"game-{game['episode']}.json", output)
            merged['games'].extend(report['games'])
        merged['evaluationInputHashes'] = {p: sha for p, sha in expected['files'].items()
                                           if p != str(Path(checkpoint).resolve())}
        merged['phpVersion'] = expected['phpVersion']
        merged['completed'] = sum(g['completed'] for g in merged['games'])
        merged['learnerWins'] = sum(g['completed'] and g['winner'] == g['learnerSeat'] for g in merged['games'])
        (output / 'report.json').write_text(json.dumps(merged, indent=2) + '\n')
        shutil.copy2(chunks[0][2] / 'latest.json', output)
        if identity(config, baseline, checkpoint, games, seed, max_steps) != expected:
            raise ValueError('Evaluation inputs changed during execution; results cannot be cached.')
        if entry is not None:
            entry.parent.mkdir(parents=True, exist_ok=True)
            # Publish only complete, checksummed artifacts. Concurrent writers
            # may finish the same key; neither may replace an existing result.
            with tempfile.TemporaryDirectory(dir=entry.parent) as temp:
                staged = Path(temp) / 'result'
                staged.mkdir()
                for p in [output / 'report.json', output / 'latest.json', *output.glob('game-*.json')]:
                    shutil.copy2(p, staged)
                manifest = {'identity': expected, 'outputs': {p.name: digest(p) for p in staged.iterdir()}}
                (staged / 'cache.json').write_text(json.dumps(manifest))
                if entry.exists() and not cache_valid(entry, expected):
                    shutil.rmtree(entry)
                if not entry.exists():
                    try:
                        staged.rename(entry)
                    except FileExistsError:
                        pass
    metadata = {'cacheHit': hit, 'cacheKey': key, 'workers': workers,
                'elapsedSeconds': round(time.monotonic() - started, 3)}
    (output / 'evaluation.json').write_text(json.dumps(metadata, indent=2) + '\n')
    return metadata


def main():
    p = argparse.ArgumentParser(description=__doc__)
    for name in ('config', 'baseline', 'checkpoint', 'output'):
        p.add_argument('--' + name, required=True)
    p.add_argument('--games', type=int, default=42)
    p.add_argument('--seed', type=int, default=9000001)
    p.add_argument('--max-steps', type=int, default=1000)
    p.add_argument('--workers', type=int, default=2)
    p.add_argument('--cache')
    print(json.dumps(evaluate(**vars(p.parse_args())), indent=2))


if __name__ == '__main__':
    main()
