"""Train independent GA archetype adapters in parallel; never deploy a model."""
import argparse
from concurrent.futures import ThreadPoolExecutor, as_completed
import copy
import fcntl
import json
from pathlib import Path
import subprocess
import time

from compare_ga_evaluations import compare
from evaluate_ga_parallel import ROOT, digest, identity


def write_json(path, value):
    temporary = path.with_suffix(path.suffix + '.tmp')
    temporary.write_text(json.dumps(value, indent=2, ensure_ascii=False) + '\n')
    temporary.replace(path)


def normalized(payload):
    result = copy.deepcopy(payload)
    if result.get('state_key_version') != 'GrandArchiveSim:linear-v1':
        raise ValueError('Requires a GA linear-v1 foundation.')
    if result.get('ga_archetype_adapters', 'frozen-base-v1') != 'frozen-base-v1':
        raise ValueError('Unknown adapter format.')
    result['ga_archetype_adapters'] = 'frozen-base-v1'
    if result.get('logits') == []:
        result['logits'] = {}
    return result


def validate_candidate(incumbent, candidate, archetype):
    """Reject all changes except the one explicitly assigned adapter bucket."""
    base, new = normalized(incumbent), normalized(candidate)
    bucket = '__archetype__:' + archetype
    base['logits'].pop(bucket, None)
    new['logits'].pop(bucket, None)
    if base != new:
        raise ValueError(f'{archetype}: worker changed foundation, another adapter, or policy settings.')


def accepted(comparison, training):
    # This is a conservative screening decision, not a strength/significance claim.
    return (all(g['completed'] for g in training['games'])
            and comparison['excludedIncompletePairs'] == 0
            and comparison['regressedOutcomes'] == 0
            and comparison['improvedOutcomes'] > 0
            and comparison['pairedAfterWins'] > comparison['pairedBeforeWins'])


def merge(incumbent, candidates):
    result = normalized(incumbent)
    seen = set()
    for archetype, candidate, keep in candidates:
        if archetype in seen:
            raise ValueError('Duplicate worker archetype.')
        seen.add(archetype)
        validate_candidate(incumbent, candidate, archetype)
        if keep:
            bucket = '__archetype__:' + archetype
            if bucket in candidate['logits']:
                result['logits'][bucket] = copy.deepcopy(candidate['logits'][bucket])
            else:
                result['logits'].pop(bucket, None)
    return result


def validate_report(report, games, start, seed, archetype, evaluation):
    if report['mode'] != ('evaluation' if evaluation else 'training'):
        raise ValueError('Unexpected worker mode.')
    if report['start'] != start or report['seed'] != seed:
        raise ValueError('Worker schedule differs.')
    if [g['episode'] for g in report['games']] != list(range(start + 1, start + games + 1)):
        raise ValueError('Incomplete worker report.')
    for i, game in enumerate(report['games']):
        if (game['learnerArchetype'] != archetype or game['learnerSeat'] != 1 + i % 2
                or game['seed'] != seed + (start + i)//2 or not game.get('initialGamestateHash')):
            raise ValueError('Incorrect archetype, seat, seed, or missing starting state.')
    if evaluation and report['initialPolicyHash'] != report['finalPolicyHash']:
        raise ValueError('Evaluation modified policy.')


def run_stage(folder, command, spec):
    """Reuse only verified completed stages; preserve interrupted attempts."""
    marker = folder.with_suffix('.complete.json')
    if marker.exists():
        saved = json.loads(marker.read_text())
        if saved['spec'] != spec or not saved['outputs']:
            raise ValueError(f'Stage identity differs: {folder}')
        if any(digest(folder / name) != sha for name, sha in saved['outputs'].items()):
            raise ValueError(f'Completed stage artifacts changed: {folder}')
        return True
    if folder.exists():
        folder.rename(folder.with_name(folder.name + f'.interrupted-{time.time_ns()}'))
    with folder.with_suffix('.log').open('w') as log:
        subprocess.run(command, cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, check=True)
    report = json.loads((folder/'report.json').read_text())
    validate_report(report, **spec['schedule'])
    outputs = [folder/'report.json', folder/'latest.json', *folder.glob('game-*.json')]
    if len(list(folder.glob('game-*.json'))) != spec['schedule']['games']:
        raise ValueError('Missing replay artifacts.')
    write_json(marker, {'spec': spec, 'outputs': {p.name: digest(p) for p in outputs}})
    return False


def run_round(config, baseline, checkpoint, output, workers=2, training_games=10,
              screen_games=12, max_steps=2000, training_seed=11000001,
              screen_seed=12000001, start=0, resume=False):
    if any(n < 2 or n % 2 for n in (training_games, screen_games)) or workers < 1 or max_steps < 1 or start < 0 or start % 2:
        raise ValueError('Use positive even game counts, even nonnegative start, and positive workers/max steps.')
    if min(training_seed + training_games//2, screen_seed + screen_games//2) > max(training_seed, screen_seed):
        raise ValueError('Training and screening seeds overlap.')
    config, baseline, checkpoint, output = [Path(p).resolve() for p in (config, baseline, checkpoint, output)]
    decks = json.loads(config.read_text())['decks']
    archetypes = sorted({str(d.get('archetypeId', d.get('name', ''))).strip() for d in decks})
    if len(decks) < 2 or '' in archetypes:
        raise ValueError('Require at least two decks with explicit archetypeId or descriptive names.')
    incumbent = json.loads(checkpoint.read_text())
    normalized(incumbent)
    # Same snapshot for all workers and future resumes; a changed runtime requires a new round.
    expected = {'inputs': identity(config, baseline, checkpoint, training_games, training_seed, max_steps),
                'screenGames': screen_games, 'screenSeed': screen_seed, 'start': start, 'archetypes': archetypes}
    if resume:
        if not output.is_dir():
            raise ValueError('Resume directory does not exist.')
    else:
        output.mkdir(parents=True, exist_ok=False)
    with (output/'round.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        manifest = output/'inputs.json'
        if resume:
            if json.loads(manifest.read_text()) != expected:
                raise ValueError('Round inputs changed; use a new output directory.')
        else:
            write_json(manifest, expected)
        summary = {'status': 'running', 'promoted': False, 'workers': workers,
                   'archetypes': {}, 'trainingGamesPerArchetype': training_games,
                   'screenGamesPerArchetype': screen_games}
        started = time.monotonic()

        def save():
            summary['elapsedSeconds'] = round(time.monotonic() - started, 3)
            write_json(output/'summary.json', summary)

        def worker(item):
            index, archetype = item
            folder = output/f'archetype-{index:02d}'
            folder.mkdir(exist_ok=True)
            common = ['php', str(ROOT/'DevTools/grand-archive/rl/train_ga_tournament.php'), '--config', str(config),
                      '--baseline', str(baseline), '--start', str(start), '--max-steps', str(max_steps),
                      '--learner-archetype', archetype]
            reused = []
            for name, source, games, seed, evaluation in (
                    ('train', checkpoint, training_games, training_seed, False),
                    ('before', checkpoint, screen_games, screen_seed, True),
                    ('after', folder/'train/latest.json', screen_games, screen_seed, True)):
                spec = {'checkpointHash': digest(source), 'schedule': dict(games=games, start=start, seed=seed,
                                                                         archetype=archetype, evaluation=evaluation)}
                command = common + ['--checkpoint', str(source), '--output', str(folder/name), '--games', str(games),
                                    '--seed', str(seed)] + (['--evaluate'] if evaluation else ['--archetype-adapters'])
                if run_stage(folder/name, command, spec):
                    reused.append(name)
                validate_candidate(incumbent, json.loads((folder/'train/latest.json').read_text()), archetype)
            training, before, after = [json.loads((folder/name/'report.json').read_text()) for name in ('train','before','after')]
            comparison = compare(before, after)
            keep = accepted(comparison, training)
            result = {'archetype': archetype, 'accepted': keep, 'comparison': comparison,
                      'completedTrainingGames': sum(g['completed'] for g in training['games']),
                      'reusedStages': reused, 'artifacts': str(folder),
                      'reason': 'Screening improvement; needs independent validation.' if keep else
                                'Retained incumbent: regression, incomplete games, or no measured improvement.'}
            write_json(folder/'decision.json', result)
            return archetype, json.loads((folder/'train/latest.json').read_text()), result

        save()
        results = []
        try:
            # Regression preflight before spending a round on games.
            with (output/'preflight.log').open('w') as log:
                for test in ('DevTools/grand-archive/rl/test_ga_policy.php', 'DevTools/grand-archive/rl/test_ga_start_reproducibility.php',
                             'DevTools/grand-archive/test-ga-unique-immortality.php', 'DevTools/grand-archive/test-ga-tweedledum-target.php'):
                    subprocess.run(['php', str(ROOT/test)], cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, check=True)
            with ThreadPoolExecutor(max_workers=workers) as pool:
                futures = {pool.submit(worker, item): item[1] for item in enumerate(archetypes)}
                for future in as_completed(futures):
                    archetype = futures[future]
                    try:
                        name, candidate, result = future.result()
                        results.append((name, candidate, result['accepted']))
                        summary['archetypes'][name] = result
                    except Exception as exc:
                        summary['archetypes'][archetype] = {'error': str(exc), 'accepted': False}
                    save()
            if any('error' in r for r in summary['archetypes'].values()):
                raise RuntimeError('One or more archetype workers failed. Resume after diagnosing; no merge published.')
            if identity(config, baseline, checkpoint, training_games, training_seed, max_steps) != expected['inputs']:
                raise ValueError('Inputs changed during round; no merge published.')
            combined = merge(incumbent, sorted(results))
            write_json(output/'candidate.json', combined)
            summary.update(status='completed', acceptedArchetypes=sum(x[2] for x in results),
                           candidateHash=digest(output/'candidate.json'),
                           next='Independently validate accepted adjustments before deployment. Use fresh seeds and advance --start in later rounds.')
            save()
            return summary
        except Exception as exc:
            summary.update(status='failed', error=str(exc))
            save()
            raise


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    for name in ('config', 'baseline', 'checkpoint', 'output'):
        parser.add_argument('--'+name, required=True)
    for name, default in (('workers',2), ('training-games',10), ('screen-games',12), ('max-steps',2000),
                          ('training-seed',11000001), ('screen-seed',12000001), ('start',0)):
        parser.add_argument('--'+name, type=int, default=default)
    parser.add_argument('--resume', action='store_true')
    result = run_round(**vars(parser.parse_args()))
    print(f"{result['status']}: {result['acceptedArchetypes']} archetypes passed screening. No model deployed.")


if __name__ == '__main__':
    main()
