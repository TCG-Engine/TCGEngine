"""Compare frozen GA checkpoints on the same evaluation schedule (no training)."""
import argparse
import json
from pathlib import Path


def compare(before, after):
    for report in (before, after):
        if report.get('mode') != 'evaluation':
            raise ValueError('Both reports must be evaluations.')
        if report['initialPolicyHash'] != report['finalPolicyHash']:
            raise ValueError('Evaluation mutated its policy.')
    for key in ('start', 'seed', 'deckHashes', 'baseline', 'sourceHashes', 'maxSteps'):
        if key not in before or key not in after or before[key] != after[key]:
            raise ValueError(f'Evaluation conditions differ or are missing: {key}')
    a, b = before['games'], after['games']
    if len(a) != len(b):
        raise ValueError('Evaluation game counts differ.')
    improved = regressed = tied = excluded = 0
    for old, new in zip(a, b):
        for key in ('seed', 'learnerSeat', 'learnerDeck', 'opponentDeck'):
            if old[key] != new[key]:
                raise ValueError(f'Evaluation matchups differ: {key}')
        if not old['completed'] or not new['completed']:
            excluded += 1
            continue
        change = new['learnerReward'] - old['learnerReward']
        improved += change > 0
        regressed += change < 0
        tied += change == 0
    return {'scheduledGames': len(a), 'pairedCompletedGames': improved + regressed + tied,
            'improvedOutcomes': improved, 'regressedOutcomes': regressed,
            'unchangedOutcomes': tied, 'excludedIncompletePairs': excluded,
            'beforeWins': before['learnerWins'], 'afterWins': after['learnerWins'],
            'note': 'Descriptive paired results only; this is not a promotion or significance test.'}


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('before', type=Path)
    parser.add_argument('after', type=Path)
    args = parser.parse_args()
    try:
        print(json.dumps(compare(json.loads(args.before.read_text()), json.loads(args.after.read_text())), indent=2))
    except (ValueError, KeyError) as exc:
        parser.error(str(exc))
