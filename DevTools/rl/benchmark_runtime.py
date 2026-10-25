#!/usr/bin/env python3
"""Alternate two local runtimes through identical seeded RL workloads.

Both checkouts must already have generated runtime/card data. This never
regenerates code or changes the card database. Trainer artifacts are retained.
"""
import argparse
import hashlib
import json
from pathlib import Path
import statistics
import subprocess
import time


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--baseline', required=True, type=Path)
    parser.add_argument('--candidate', type=Path, default=Path(__file__).resolve().parents[2])
    parser.add_argument('--root', default='GrandArchiveSim')
    parser.add_argument('--deck-file', default='DevTools/rl/real_deck.txt')
    parser.add_argument('--episodes', type=int, default=20)
    parser.add_argument('--max-steps', type=int, default=500)
    parser.add_argument('--seed', type=int, default=123)
    parser.add_argument('--repeats', type=int, default=3)
    parser.add_argument('--php', default='php')
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    if min(args.episodes, args.max_steps, args.repeats) < 1:
        parser.error('episodes, max-steps and repeats must be positive')
    report = {'parameters': {k: str(v) if isinstance(v, Path) else v for k, v in vars(args).items()}, 'runs': []}
    for repeat in range(args.repeats):
        # Reverse order each repeat to reduce cache/thermal ordering bias.
        order = ['baseline', 'candidate'] if repeat % 2 == 0 else ['candidate', 'baseline']
        for label in order:
            repo = getattr(args, label).resolve()
            command = [args.php, '-d', 'error_reporting=22527', 'DevTools/rl/train_selfplay_php.php',
                       '--root', args.root, '--deck-file', args.deck_file,
                       '--episodes', str(args.episodes), '--max-steps', str(args.max_steps),
                       '--seed', str(args.seed), '--memory-only', '--log-every', str(args.episodes),
                       '--checkpoint-every', str(args.episodes)]
            started = time.perf_counter()
            result = subprocess.run(command, cwd=repo, text=True, capture_output=True)
            wall = time.perf_counter() - started
            log = args.output.with_suffix(f'.{repeat}.{label}.log')
            log.write_text(result.stdout + result.stderr)
            # Trainer emits progress followed by its final JSON result.
            try:
                payload = json.loads(result.stdout[result.stdout.rfind('\n{') + 1:])
                if result.returncode or not payload.get('success'):
                    raise ValueError('trainer failed')
                config = json.loads((Path(payload['runDir']) / 'run_config.json').read_text())
            except (ValueError, KeyError, OSError) as exc:
                raise SystemExit(f'{label} failed; inspect {log}: {exc}')
            replay = json.loads(Path(config['finalReplay']).read_text())
            action_digest = hashlib.sha256(json.dumps(replay['actions'], sort_keys=True).encode()).hexdigest()
            record = {'finalReplayActionsHash': action_digest,
                      'memoryOnlyResolved': replay.get('memoryOnlyResolved'), 'label': label, 'repeat': repeat, 'wallSeconds': wall,
                      'runDir': payload['runDir'], **config['summary']}
            record['trainerStepsPerSecond'] = record['stepsPerSecond']
            record['stepsPerSecond'] = record['totalSteps'] / wall
            report['runs'].append(record)
            args.output.write_text(json.dumps(report, indent=2) + '\n')
            print(json.dumps(record), flush=True)
            if record['failedEpisodes']:
                raise SystemExit(f'{label} had engine/action failures; benchmark is invalid')
    medians = {label: statistics.median(r['stepsPerSecond'] for r in report['runs'] if r['label'] == label)
               for label in ('baseline', 'candidate')}
    report['medianWallSeconds'] = {label: statistics.median(r['wallSeconds'] for r in report['runs'] if r['label'] == label)
                                   for label in ('baseline', 'candidate')}
    report['wallTimeChangePercent'] = (report['medianWallSeconds']['candidate'] / report['medianWallSeconds']['baseline'] - 1) * 100
    report['medianStepsPerSecond'] = medians
    report['throughputChangePercent'] = (medians['candidate'] / medians['baseline'] - 1) * 100
    args.output.write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps({k: report[k] for k in ('medianStepsPerSecond', 'throughputChangePercent', 'medianWallSeconds', 'wallTimeChangePercent')}))


if __name__ == '__main__':
    main()
