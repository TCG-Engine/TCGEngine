"""Compare frozen GA checkpoints on the same evaluation schedule (no training)."""
import argparse
import json
from pathlib import Path


def compare(before, after, _group=True):
    for report in (before, after):
        if report.get('mode') != 'evaluation':
            raise ValueError('Both reports must be evaluations.')
        if report['initialPolicyHash'] != report['finalPolicyHash']:
            raise ValueError('Evaluation mutated its policy.')
    for key in ('start', 'seed', 'deckHashes', 'baseline', 'sourceHashes', 'maxSteps'):
        if key not in before or key not in after or before[key] != after[key]:
            raise ValueError(f'Evaluation conditions differ or are missing: {key}')
    for key in ('evaluationInputHashes', 'phpVersion'):
        if (key in before or key in after) and before.get(key) != after.get(key):
            raise ValueError(f'Evaluation conditions differ: {key}')
    a, b = before['games'], after['games']
    if len(a) != len(b):
        raise ValueError('Evaluation game counts differ.')
    improved = regressed = tied = excluded = 0
    paired_before_wins = paired_after_wins = 0
    for old, new in zip(a, b):
        for key in ('seed', 'learnerSeat', 'learnerDeck', 'opponentDeck'):
            if old[key] != new[key]:
                raise ValueError(f'Evaluation matchups differ: {key}')
        if old.get('learnerArchetype', old['learnerDeck']) != new.get('learnerArchetype', new['learnerDeck']):
            raise ValueError('Evaluation archetypes differ.')
        if not old.get('initialGamestateHash') or not new.get('initialGamestateHash'):
            raise ValueError('Starting-state hashes missing; rerun evaluation or recover hashes from its saved replays.')
        if old['initialGamestateHash'] != new['initialGamestateHash']:
            raise ValueError('Evaluation starting states differ despite matching schedule seeds.')
        if not old['completed'] or not new['completed']:
            excluded += 1
            continue
        paired_before_wins += old['learnerReward'] > 0
        paired_after_wins += new['learnerReward'] > 0
        change = new['learnerReward'] - old['learnerReward']
        improved += change > 0
        regressed += change < 0
        tied += change == 0
    result = {'beforeChooser': before.get('learnerHeuristic') or 'learned-policy',
            'afterChooser': after.get('learnerHeuristic') or 'learned-policy',
            'scheduledGames': len(a), 'pairedCompletedGames': improved + regressed + tied,
            'improvedOutcomes': improved, 'regressedOutcomes': regressed,
            'unchangedOutcomes': tied, 'excludedIncompletePairs': excluded,
            'beforeWins': before['learnerWins'], 'afterWins': after['learnerWins'],
            'pairedBeforeWins': paired_before_wins, 'pairedAfterWins': paired_after_wins,
            'note': 'Descriptive paired results only; this is not a promotion or significance test.'}

    if _group:
        result['byArchetype'] = {}
        for archetype in sorted({g.get('learnerArchetype', g['learnerDeck']) for g in a}):
            reports = []
            for report in (before, after):
                games = [g for g in report['games'] if g.get('learnerArchetype', g['learnerDeck']) == archetype]
                reports.append({**report, 'games': games, 'learnerWins': sum(g['completed'] and g['learnerReward'] > 0 for g in games)})
            result['byArchetype'][archetype] = compare(*reports, _group=False)
    return result


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('before', type=Path)
    parser.add_argument('after', type=Path)
    args = parser.parse_args()
    try:
        print(json.dumps(compare(json.loads(args.before.read_text()), json.loads(args.after.read_text())), indent=2))
    except (ValueError, KeyError) as exc:
        parser.error(str(exc))
