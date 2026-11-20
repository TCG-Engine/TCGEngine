"""Pinned archetype improvement batches with full-roster before/after evaluation."""
import argparse
import json
from pathlib import Path
import shutil
from ga_opponent_pool import snapshot, verify, evaluate_pool, digest, ROOT, FREEZER


def read(path):
    return json.loads(Path(path).read_text())


def write(path, value):
    Path(path).write_text(json.dumps(value, indent=2) + '\n')


def start(output, pool, config, checkpoint, targets, seed, validation_seed):
    output, pool, config = map(lambda p: Path(p).resolve(), (output, pool, config))
    data = read(config)
    roster = [d.get('archetypeId', d['name']) for d in data['decks']]
    entries = read(pool)['opponents']
    if not targets or len(set(targets)) != len(targets) or not set(targets) <= set(roster):
        raise ValueError('Select unique configured archetypes')
    if seed == validation_seed:
        raise ValueError('Validation must use a separate seed')
    if not any(e.get('role') == 'reference' for e in entries):
        raise ValueError('Retain a historical reference in every batch')
    for entry in entries:
        verify(pool.parent / entry['path'])
    output.mkdir(parents=True, exist_ok=False)
    for i, deck in enumerate(data['decks']):
        shutil.copyfile(config.parent / deck['path'], output / f'deck-{i}.txt')
        deck['path'] = f'deck-{i}.txt'
    write(output / 'config.json', data)
    shutil.copyfile(checkpoint, output / 'checkpoint.json')
    copied = []
    for i, entry in enumerate(entries):
        dest = output / 'opponents' / str(i)
        shutil.copytree(pool.parent / entry['path'], dest)
        copied.append({**entry, 'path': str(dest.relative_to(output))})
    write(output / 'pool.json', {'opponents': copied})
    snapshot(output / 'starting-chooser', 'batch-start')
    hashes = {str(p.relative_to(output)): digest(p) for p in output.rglob('*') if p.is_file()}
    write(output / 'batch.json', {'targets': targets, 'roster': roster, 'seed': seed,
          'validationSeed': validation_seed, 'games': 2 * (len(roster) - 1), 'inputHashes': hashes})


def check(batch):
    batch = Path(batch)
    state = read(batch / 'batch.json')
    for name, sha in state['inputHashes'].items():
        if digest(batch / name) != sha:
            raise ValueError(f'Pinned batch input changed: {name}')
    return state


def evaluate(batch, phase, workers=2):
    batch = Path(batch).resolve()
    state = check(batch)
    if phase == 'before':
        expected = read(batch / 'starting-chooser/snapshot.json')['sourceHashes']
        if any(digest(ROOT / 'GrandArchiveSim' / n) != sha for n, sha in expected.items()):
            raise ValueError('Run before evaluation before modifying chooser policies')
    else:
        for stage in ('screen', 'validation'):
            if not (batch / 'before' / stage / 'report.json').exists():
                raise ValueError('Complete both before evaluations first')
    dest = batch / phase
    dest.mkdir(exist_ok=False)
    snapshot(dest / 'chooser', phase)
    for stage, seed in [('screen', state['seed']), ('validation', state['validationSeed'])]:
        evaluate_pool(batch / 'pool.json', batch / 'config.json', batch / 'checkpoint.json',
                      dest / stage, state['roster'], state['games'], seed, 2000, workers)
        expected = read(dest / 'chooser/snapshot.json')['sourceHashes']
        if any(digest(ROOT / 'GrandArchiveSim' / n) != sha for n, sha in expected.items()):
            raise ValueError('Chooser changed between evaluation stages')
    check(batch)


def compare_rows(before, after):
    key = lambda r: (r['archetype'], r['opponentVersion'])
    a, b = {key(r): r for r in before}, {key(r): r for r in after}
    if a.keys() != b.keys() or len(a) != len(before) or len(b) != len(after):
        raise ValueError('Evaluation coverage differs')
    if list(a) != list(b):
        raise ValueError('Evaluation order differs')
    result = []
    for k, old in a.items():
        new = b[k]
        if old['scheduled'] != new['scheduled']:
            raise ValueError('Game counts differ')
        result.append({'archetype': k[0], 'opponentVersion': k[1],
                       'deltaWins': new['wins'] - old['wins'],
                       'complete': old['completed'] == new['completed'] == new['scheduled']})
    return result


def review(batch):
    batch = Path(batch)
    state = check(batch)
    stages = {}
    for stage in ('screen', 'validation'):
        before = read(batch / 'before' / stage / 'report.json')
        after = read(batch / 'after' / stage / 'report.json')
        if before['seed'] != after['seed']:
            raise ValueError('Seeds differ')
        stages[stage] = compare_rows(before['results'], after['results'])
        for row, old, new in zip(stages[stage], before['results'], after['results']):
            a = read(batch / 'before' / stage / old['report'])
            b = read(batch / 'after' / stage / new['report'])
            for field in ('seed', 'deckHashes', 'baseline', 'maxSteps'):
                if a[field] != b[field]:
                    raise ValueError(f'Comparison conditions differ: {field}')
            if any(r['initialPolicyHash'] != r['finalPolicyHash'] for r in (a, b)):
                raise ValueError('Evaluation mutated checkpoint')
            if len(a['games']) != len(b['games']) or len(a['games']) != old['scheduled']:
                raise ValueError('Incomplete game schedule')
            regressed = 0
            for x, y in zip(a['games'], b['games']):
                for field in ('seed', 'learnerSeat', 'learnerDeck', 'opponentDeck', 'initialGamestateHash'):
                    if field not in x or x[field] != y.get(field) or (field == 'initialGamestateHash' and not x[field]):
                        raise ValueError(f'Paired game differs: {field}')
                regressed += x['completed'] and y['completed'] and y['learnerReward'] < x['learnerReward']
            row['regressedGames'] = regressed
    regressions = [dict(r, stage=s) for s, rows in stages.items() for r in rows
                   if r['deltaWins'] < 0 or r.get('regressedGames', 0) or not r['complete']]
    candidates = [a for a in state['targets'] if not regressions and all(
        sum(r['deltaWins'] for r in rows if r['archetype'] == a) > 0 for rows in stages.values())]
    result = {'stages': stages, 'regressionsOrIncomplete': regressions,
              'eligibleForDecisionReview': candidates,
              'note': 'Screen only: inspect decisions and matchup regressions before accepting. No automatic promotion. Shared chooser versions affect every archetype.'}
    write(batch / 'review.json', result)
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='command', required=True)
    p = sub.add_parser('start')
    for n in ('output', 'pool', 'config', 'checkpoint'):
        p.add_argument('--' + n, required=True)
    p.add_argument('--archetype', action='append', required=True)
    p.add_argument('--seed', type=int, required=True)
    p.add_argument('--validation-seed', type=int, required=True)
    p = sub.add_parser('evaluate'); p.add_argument('--batch', required=True)
    p.add_argument('--phase', choices=['before', 'after'], required=True)
    p.add_argument('--workers', type=int, default=2)
    p = sub.add_parser('review'); p.add_argument('--batch', required=True)
    args = parser.parse_args()
    if args.command == 'start':
        start(args.output, args.pool, args.config, args.checkpoint, args.archetype, args.seed, args.validation_seed)
    elif args.command == 'evaluate':
        evaluate(args.batch, args.phase, args.workers)
    else:
        print(json.dumps(review(args.batch), indent=2))

if __name__ == '__main__':
    main()
