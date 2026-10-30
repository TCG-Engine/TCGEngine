"""Run an isolated GA imitation, RL, and two-seed evaluation cycle. Never deploys."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]

def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--config', required=True)
    p.add_argument('--baseline', required=True)
    p.add_argument('--output', required=True, type=Path)
    p.add_argument('--imitation-games', type=int, default=42)
    p.add_argument('--training-games', type=int, default=10)
    p.add_argument('--evaluation-games', type=int, default=42)
    p.add_argument('--max-steps', type=int, default=1000)
    a = p.parse_args()
    if any(n < 2 or n % 2 for n in (a.imitation_games, a.training_games, a.evaluation_games)):
        p.error('Game counts must be positive and even.')
    a.output.mkdir(parents=True, exist_ok=False)
    common = ['php', str(ROOT/'DevTools/rl/train_ga_tournament.php'), '--config', a.config,
              '--baseline', a.baseline, '--max-steps', str(a.max_steps)]
    def run(name, games, seed, *extra):
        out = a.output/name
        with (a.output/(name+'.log')).open('w') as log:
            subprocess.run(common+['--output',str(out),'--games',str(games),'--seed',str(seed),*extra], cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, check=True)
        return str(out/'latest.json')
    teacher = run('imitation', a.imitation_games, 50001, '--representation','linear-v1','--imitate')
    heldout = run('teacher-heldout', a.evaluation_games, 60001, '--checkpoint',teacher,'--imitate')
    # This teacher rollout is used only as observations, never as the RL starting model.
    for name, checkpoint in [('before', teacher)]:
        result = subprocess.check_output(['php',str(ROOT/'DevTools/rl/ga_imitation_fidelity.php'),'--checkpoint',checkpoint,'--replays',str(a.output/'teacher-heldout')],cwd=ROOT)
        (a.output/(name+'-fidelity.json')).write_bytes(result)
    trained = run('rl',a.training_games,70001,'--checkpoint',teacher)
    comparisons = []
    for seed in (4000001,5000001):
        for label, checkpoint in [('before',teacher),('after',trained)]:
            run(f'{label}-{seed}',a.evaluation_games,seed,'--checkpoint',checkpoint,'--evaluate')
        result = subprocess.check_output(['python3',str(ROOT/'DevTools/rl/compare_ga_evaluations.py'),str(a.output/f'before-{seed}/report.json'),str(a.output/f'after-{seed}/report.json')],cwd=ROOT)
        comparisons.append(json.loads(result))
    (a.output/'comparison.json').write_text(json.dumps(comparisons,indent=2))
    print(f'Results saved to {a.output}. No model deployed.')

if __name__ == '__main__':
    main()
