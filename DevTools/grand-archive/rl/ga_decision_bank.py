"""Compare paired replay decisions and collect public policy inputs for later review.
No win/loss result is treated as an action-quality label. No hidden state is exported.
"""
import argparse
import hashlib
import json
from pathlib import Path
from compare_ga_evaluations import compare


def digest(value):
    return hashlib.sha256(json.dumps(value, sort_keys=True, separators=(',', ':')).encode()).hexdigest()


def first_difference(before, after):
    if not before.get('initialGamestateHash') or before['initialGamestateHash'] != after.get('initialGamestateHash'):
        raise ValueError('Replay initial states differ or are missing')
    for i, (a, b) in enumerate(zip(before['actions'], after['actions'])):
        if (a['turnPlayer'], a['action']) != (b['turnPlayer'], b['action']):
            return i
    return None


def position(step, archetype, source_identity=None):
    observation = step.get('observation')
    keys, indices = step.get('actionKeys'), step.get('legalIndices')
    if observation is None or not keys or indices is None:
        return None  # Legacy replay: never infer support or invent missing inputs.
    def key(i):
        return keys[str(i)] if isinstance(keys, dict) else keys[i]
    choices = sorted(set(key(i) for i in indices))
    chosen = step.get('actionKey')
    if chosen not in choices:
        return None
    support = step.get('decisionSupport') or {'status': 'unknown', 'kind': 'legacy'}
    status = support.get('status', 'unknown')
    category = {'missing': 'missing_support', 'supported': 'decision_review'}.get(status, 'unknown_support')
    payload = {'archetype': archetype, 'observation': observation, 'actionKeys': choices, 'sourceIdentity': source_identity,
               'supportIdentity': digest(support)}
    return {'id': digest(payload), **payload, 'category': category, 'support': support,
            'qualityLabel': 'unreviewed', 'examples': []}


def build(before_dir, after_dir, bank):
    before_dir, after_dir = Path(before_dir), Path(after_dir)
    a, b = [json.loads((p / 'report.json').read_text()) for p in (before_dir, after_dir)]
    comparison = compare(a, b)  # Frozen policy, source, schedule and starting-state guards.
    findings = []
    for ga, gb in zip(a['games'], b['games']):
        paths = [p / f"game-{g['episode']}.json" for p, g in [(before_dir, ga), (after_dir, gb)]]
        replays = [json.loads(p.read_text()) for p in paths]
        for replay, game in zip(replays, [ga, gb]):
            if replay['initialGamestateHash'] != game['initialGamestateHash'] or replay['seed'] != game['seed']:
                raise ValueError('Replay does not match report')
        divergence = first_difference(*replays)
        archetype = ga.get('learnerArchetype', ga['learnerDeck'])
        findings.append({'episode': ga['episode'], 'archetype': archetype,
                         'firstDifferentStep': None if divergence is None else divergence + 1,
                         'completedPair': ga['completed'] and gb['completed'],
                         'rewardBefore': ga['learnerReward'], 'rewardAfter': gb['learnerReward'],
                         'causalMistakeEstablished': False})
        for side, replay, path in zip(['before', 'after'], replays, paths):
            replay_hash = digest(replay)
            for i, step in enumerate(replay['actions']):
                if not step.get('trainingPlayer') or step.get('legalCount', 0) < 2:
                    continue
                item = position(step, archetype, digest(a['sourceHashes']))
                if item is None:
                    continue
                # Retain support gaps anywhere, plus the first differing decision and
                # stable decisions as a preservation bank. Never align diverged futures.
                item = bank.setdefault(item['id'], item)
                example = {'replay': str(path.resolve()), 'replayHash': replay_hash,
                           'step': i + 1, 'side': side, 'chosenActionKey': step['actionKey'],
                           'firstDivergence': i == divergence,
                           'split': 'evaluation_review_only'}
                if example not in item['examples']:
                    item['examples'].append(example)
    return {'comparison': comparison, 'games': findings,
            'note': 'First divergence is a review lead, not a proven mistake. Evaluation positions must not become training targets while their seeds remain holdout.'}


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('before', type=Path); p.add_argument('after', type=Path)
    p.add_argument('--bank', type=Path, required=True)
    p.add_argument('--output', type=Path, required=True)
    args = p.parse_args()
    bank = json.loads(args.bank.read_text()) if args.bank.exists() else {}
    result = build(args.before, args.after, bank)
    result['bankCounts'] = {c: sum(x['category'] == c for x in bank.values()) for c in ['missing_support', 'decision_review', 'unknown_support']}
    for path, data in [(args.bank, bank), (args.output, result)]:
        path.parent.mkdir(parents=True, exist_ok=True)
        temp = path.with_suffix(path.suffix + '.tmp')
        temp.write_text(json.dumps(data, indent=2, ensure_ascii=False) + '\n'); temp.replace(path)
    print(json.dumps({'games': len(result['games']), **result['bankCounts']}))


if __name__ == '__main__':
    main()
