"""Train, screen, then evaluate frozen GA candidates. Never deploys a model."""
import argparse
import json
from pathlib import Path
import subprocess
import time

from compare_ga_evaluations import compare
from evaluate_ga_parallel import evaluate

ROOT = Path(__file__).resolve().parents[3]


def screen_passes(comparison):
    # A triage gate, not a significance test. Ties advance so a small screen
    # does not discard improvements that occur outside its matchups.
    return (comparison['excludedIncompletePairs'] == 0
            and comparison['regressedOutcomes'] == 0
            and comparison['pairedAfterWins'] >= comparison['pairedBeforeWins'])


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--hypothesis', default='Evaluate the RL continuation against its frozen teacher checkpoint.')
    p.add_argument('--config', required=True)
    p.add_argument('--baseline', required=True)
    p.add_argument('--output', required=True, type=Path)
    p.add_argument('--imitation-games', type=int, default=42)
    p.add_argument('--training-games', type=int, default=10)
    p.add_argument('--screen-games', type=int, default=12)
    p.add_argument('--evaluation-games', type=int, default=42)
    p.add_argument('--max-steps', type=int, default=1000)
    p.add_argument('--workers', type=int, default=2)
    p.add_argument('--cache', type=Path, default=ROOT/'DevTools/rl/artifacts/evaluation-cache')
    p.add_argument('--archetype-adapters', action='store_true', help='Freeze shared weights and train own-archetype adjustments.')
    p.add_argument('--teacher-checkpoint', type=Path)
    p.add_argument('--candidate-checkpoint', type=Path)
    p.add_argument('--screen-seed', type=int, default=9100001)
    p.add_argument('--evaluation-seeds', type=int, nargs=2, default=[9200001, 9300001])
    a = p.parse_args()
    if any(n < 2 or n % 2 for n in (a.imitation_games, a.training_games, a.screen_games, a.evaluation_games)):
        p.error('Game counts must be positive and even.')
    if a.workers < 1 or a.max_steps < 1:
        p.error('Workers and max steps must be positive.')
    if a.candidate_checkpoint and not a.teacher_checkpoint:
        p.error('A candidate checkpoint requires its teacher checkpoint.')
    # Keep screening and final validation seed ranges disjoint, including the
    # fixed imitation/training ranges used by this runner.
    ranges = [(50001, a.imitation_games//2), (70001, a.training_games//2),
              (a.screen_seed, a.screen_games//2),
              *[(seed, a.evaluation_games//2) for seed in a.evaluation_seeds]]
    if any(max(s, t) < min(s+n, t+m) for i, (s,n) in enumerate(ranges) for t,m in ranges[i+1:]):
        p.error('Training, screening, and evaluation seed ranges must not overlap.')
    for path in (a.teacher_checkpoint, a.candidate_checkpoint):
        if path and not path.is_file():
            p.error(f'Checkpoint not found: {path}')
    a.output.mkdir(parents=True, exist_ok=False)
    started = time.monotonic()
    summary = {'status': 'running', 'promoted': False, 'hypothesis': a.hypothesis, 'stages': []}

    def save():
        summary['elapsedSeconds'] = round(time.monotonic() - started, 3)
        (a.output/'summary.json').write_text(json.dumps(summary, indent=2)+'\n')

    common = ['php', str(ROOT/'DevTools/grand-archive/rl/train_ga_tournament.php'), '--config', str(Path(a.config).resolve()),
              '--baseline', str(Path(a.baseline).resolve()), '--max-steps', str(a.max_steps)]

    def train(name, games, seed, *extra):
        stage_start = time.monotonic()
        out = a.output/name
        with (a.output/(name+'.log')).open('w') as log:
            subprocess.run(common+['--output',str(out.resolve()),'--games',str(games),'--seed',str(seed),*extra],
                           cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, check=True)
        report = json.loads((out/'report.json').read_text())
        summary['stages'].append({'name': name, 'games': len(report['games']), 'completed': report['completed'],
                                  'elapsedSeconds': round(time.monotonic()-stage_start,3)})
        save()
        return str((out/'latest.json').resolve())

    def match(name, games, seed, teacher, candidate):
        for label, checkpoint in [('before', teacher), ('after', candidate)]:
            stage = name+'-'+label
            metadata = evaluate(a.config, a.baseline, checkpoint, a.output/stage,
                                games, seed, a.max_steps, a.workers, a.cache)
            summary['stages'].append({'name': stage, **metadata})
            save()
        result = compare(*[json.loads((a.output/(name+'-'+label)/'report.json').read_text())
                           for label in ('before','after')])
        (a.output/(name+'-comparison.json')).write_text(json.dumps(result, indent=2)+'\n')
        return result

    save()
    try:
        preflight_start = time.monotonic()
        with (a.output/'preflight.log').open('w') as log:
            for test in ('DevTools/grand-archive/rl/test_ga_start_reproducibility.php',
                         'DevTools/grand-archive/rl/test_ga_policy.php',
                         'DevTools/grand-archive/test-ga-unique-immortality.php',
                         'DevTools/grand-archive/test-ga-tweedledum-target.php'):
                subprocess.run(['php', str(ROOT/test)], cwd=ROOT, stdout=log,
                               stderr=subprocess.STDOUT, check=True)
        summary['stages'].append({'name': 'preflight', 'passed': True,
                                  'elapsedSeconds': round(time.monotonic()-preflight_start,3)})
        save()
        teacher = str(a.teacher_checkpoint.resolve()) if a.teacher_checkpoint else train(
            'imitation', a.imitation_games, 50001, '--representation','linear-v1','--imitate')
        candidate = str(a.candidate_checkpoint.resolve()) if a.candidate_checkpoint else train(
            'rl', a.training_games, 70001, '--checkpoint',teacher,
            *(['--archetype-adapters'] if a.archetype_adapters else []))
        summary['screen'] = match('screen', a.screen_games, a.screen_seed, teacher, candidate)
        summary['screenPassed'] = screen_passes(summary['screen'])
        save()
        if not summary['screenPassed']:
            summary['status'] = 'screen_rejected'
            summary['next'] = 'Inspect screening regressions or incomplete games; full validation was skipped.'
        else:
            summary['comparisons'] = [match(f'evaluation-{seed}', a.evaluation_games, seed, teacher, candidate)
                                      for seed in a.evaluation_seeds]
            (a.output/'comparison.json').write_text(json.dumps(summary['comparisons'], indent=2)+'\n')
            summary['status'] = 'evaluated'
            summary['next'] = 'Review final validation; no model was promoted. Do not tune on validation seeds.'
        save()
    except Exception as exc:
        summary['status'], summary['error'] = 'failed', str(exc)
        save()
        raise
    print(f"{summary['status']}: results saved to {a.output}. No model deployed.")


if __name__ == '__main__':
    main()
