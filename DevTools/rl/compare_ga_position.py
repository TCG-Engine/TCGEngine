"""Compare bounded heuristic continuations from a verified replay decision.
This is offline review, never training labels or deployment authorization.
"""
import argparse
import hashlib
import json
import subprocess
from pathlib import Path


def compare_position(replay, step, horizon=100, seed=1):
    root = Path(__file__).resolve().parents[2]
    cmd = ['php', str(Path(__file__).with_name('ga_position_worker.php')),
           '--replay=' + str(Path(replay).resolve()), '--step=' + str(step)]
    def run(extra):
        result = subprocess.run(cmd + extra, cwd=root, capture_output=True, text=True, timeout=180)
        if result.returncode:
            raise RuntimeError(result.stderr.strip() or result.stdout.strip())
        return json.loads(result.stdout)
    initial = run([])
    branches = []
    # Each branch starts in a fresh PHP process to isolate globals and RNG.
    for index in range(len(initial['actionKeys'])):
        branch = run([f'--choice={index}', f'--horizon={horizon}', f'--seed={seed}'])
        for key in ('positionHash', 'observation', 'actionKeys', 'choices'):
            if branch[key] != initial[key]:
                raise RuntimeError('Reconstructed position changed between branches: ' + key)
        branches.append(branch['branch'])
    return {'replayHash': hashlib.sha256(Path(replay).read_bytes()).hexdigest(),
            'step': step, 'horizon': horizon, 'seed': seed, 'position': initial,
            'branches': branches, 'qualityLabel': 'unreviewed',
            'limitations': 'One saved hidden-state realization; same RNG seed does not guarantee identical later draws after different random calls. Bounded outcomes are not causal quality labels.'}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('replay', type=Path)
    parser.add_argument('--step', type=int, required=True)
    parser.add_argument('--horizon', type=int, default=100)
    parser.add_argument('--seed', type=int, default=1)
    parser.add_argument('--seeds', type=int, nargs='+', help='Compare the same position across distinct continuation seeds')
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    if args.seeds:
        if len(set(args.seeds)) != len(args.seeds):
            parser.error('--seeds must be distinct')
        result = compare_seeds(args.replay, args.step, args.horizon, args.seeds)
    else:
        result = compare_position(args.replay, args.step, args.horizon, args.seed)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps({'step': args.step, 'qualityLabel': 'unreviewed',
                      'summary': result.get('summary', {'branches': len(result.get('branches', []))})}))


def compare_seeds(replay, step, horizon, seeds):
    if not seeds or len(set(seeds)) != len(seeds):
        raise ValueError('Provide distinct continuation seeds')
    runs = [compare_position(replay, step, horizon, seed) for seed in seeds]
    first = runs[0]
    for run in runs[1:]:
        if run['replayHash'] != first['replayHash'] or run['position'] != first['position']:
            raise RuntimeError('Reconstructed position changed between seeds')
    summary = []
    for choice, key in enumerate(first['position']['actionKeys']):
        branches = [run['branches'][choice] for run in runs]
        wins = losses = draws = unfinished = 0
        for branch in branches:
            terminal = branch['terminal']
            if not terminal.get('isTerminal'):
                unfinished += 1
            elif str(terminal.get('winner')) == str(first['position']['seat']):
                wins += 1
            elif str(terminal.get('winner')) in ('1', '2'):
                losses += 1
            else:
                draws += 1
        summary.append({'choice': choice, 'actionKey': key, 'action': first['position']['choices'][choice], 'wins': wins,
                        'losses': losses, 'draws': draws, 'unfinished': unfinished,
                        'actionCounts': [branch['steps'] for branch in branches]})
    return {'runs': runs, 'summary': summary, 'qualityLabel': 'unreviewed',
            'limitations': first['limitations'] + ' Repeated seeds share one saved position; they are not independent game samples.'}


if __name__ == '__main__':
    main()
