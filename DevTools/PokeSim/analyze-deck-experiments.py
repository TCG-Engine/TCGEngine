"""Compare fixed-policy experiments, pairing differences by seed (both orders cluster together)."""
import argparse
import csv
import json
import math
from pathlib import Path


def compare(baseline, variant, metric, order=None):
    def index(run):
        return {(r['seed'], r['firstPlayer']): r for r in run['results']}
    base, other = index(baseline), index(variant)
    differences = []
    for seed in range(baseline['seed'], baseline['seed'] + baseline['pairs']):
        values = []
        for first in ([order] if order else [1, 2]):
            a, b = base[(seed, first)], other[(seed, first)]
            if a['status'] != 'complete' or b['status'] != 'complete':
                break
            if metric == 'win':
                values.append(int(b['winner'] == 1) - int(a['winner'] == 1))
            else:
                get = lambda r: next(o for o in r['openingStats'] if o['player'] == 1)
                # Unreached opportunities count as zero here; denominators are all completed games.
                values.append(int(get(b)[metric]) - int(get(a)[metric]))
        else:
            differences.append(sum(values) / len(values))
    if not differences:
        return {'pairs': 0, 'delta': None, 'approximate95CI': None}
    n = len(differences)
    mean = sum(differences) / n
    variance = sum((x - mean) ** 2 for x in differences) / (n - 1) if n > 1 else None
    margin = 1.96 * math.sqrt(variance / n) if variance is not None else None
    return {'pairs': n, 'delta': mean,
            'approximate95CI': [max(-1, mean - margin), min(1, mean + margin)] if margin is not None else None}


def subject_openings(run):
    """Experiments change Seat 1 only, even when both seats share a deck profile."""
    groups = {}
    for game in run['results']:
        for opening in game.get('openingStats', []):
            if opening['player'] != 1:
                continue
            order = opening['order']
            group = groups.setdefault(order, {'deck': opening['deck'], 'order': order, 'goal': opening['goal'],
                'blenderTrackedGames': 0, 'blenderTurn1Played': 0, 'blenderByOpportunityPlayed': 0, 'games': 0, 'reached': 0, 'earlyEnded': 0, 'incomplete': 0, 'declared': 0, 'resolved': 0,
                'goalAttacks': 0, 'enabled': 0, 'blockers': {}, 'conversion': {'enabled': {'games': 0, 'wins': 0},
                                                                          'missed': {'games': 0, 'wins': 0}}})
            if game['status'] != 'complete' or opening['status'] == 'incomplete':
                group['incomplete'] += 1
                continue
            group['games'] += 1
            if all(metric in opening for metric in ['blenderTurn1Played', 'blenderByOpportunityPlayed']):
                group['blenderTrackedGames'] += 1
                for metric in ['blenderTurn1Played', 'blenderByOpportunityPlayed']:
                    group[metric] += int(opening[metric])
            if opening['status'] == 'game_ended_before_opportunity':
                group['earlyEnded'] += 1
                continue
            if opening['status'] != 'complete':
                continue
            group['reached'] += 1
            for dest, src in [('declared', 'attackDeclared'), ('resolved', 'attackResolved'),
                              ('goalAttacks', 'goalAttack'), ('enabled', 'fullyEnabledAttack')]:
                group[dest] += int(opening[src])
            for blocker in opening['blockers']:
                group['blockers'][blocker] = group['blockers'].get(blocker, 0) + 1
            conversion = group['conversion']['enabled' if opening['fullyEnabledAttack'] else 'missed']
            conversion['games'] += 1
            conversion['wins'] += int(game['winner'] == 1)
    for group in groups.values():
        group['blenderTurn1RateAllGames'] = group['blenderTurn1Played']/group['games'] if group['games'] and group['blenderTrackedGames']==group['games'] else None
        group['blenderByOpportunityRateAllGames'] = group['blenderByOpportunityPlayed']/group['games'] if group['games'] and group['blenderTrackedGames']==group['games'] else None
        group['attackRateReached'] = group['declared'] / group['reached'] if group['reached'] else None
        group['enabledRateReached'] = group['enabled'] / group['reached'] if group['reached'] else None
        group['enabledRateAllGames'] = group['enabled'] / group['games'] if group['games'] else None
    return groups


def analyze(directory, phase):
    runs = [json.loads(p.read_text()) for p in sorted(directory.glob(phase + '-*.json')) if p.name != phase + '-comparison.json']
    baseline = next(r for r in runs if r['variant'] == 'baseline')
    output = []
    for run in runs:
        for key in ['policy', 'opponent', 'seed', 'pairs']:
            if run[key] != baseline[key]:
                raise ValueError('Incompatible experiments: ' + key)
        if run.get('base','sinistcha') != baseline.get('base','sinistcha'):
            raise ValueError('Incompatible experiments: base deck')
        if run.get('suite','default') != baseline.get('suite','default'):
            raise ValueError('Incompatible experiments: suite')
        summary = run['summary']
        opening = subject_openings(run)
        output.append({'variant': run['variant'], 'changes': run['changes'],
                       'games': summary['completed'], 'incomplete': summary['incomplete'],
                       'winRate': (summary['seat1FirstWins'] + summary['seat1SecondWins']) / summary['completed'] if summary['completed'] else None,
                       'firstWinRate': summary['seat1FirstWins'] / summary['seat1FirstGames'] if summary['seat1FirstGames'] else None,
                       'secondWinRate': summary['seat1SecondWins'] / summary['seat1SecondGames'] if summary['seat1SecondGames'] else None,
                       'firstOpening': opening['first'], 'secondOpening': opening['second'],
                       'pairedWin': compare(baseline, run, 'win'),
                       'pairedSecondBlender': compare(baseline, run, 'blenderTurn1Played', 2) if 'blenderTurn1Played' in run['results'][0]['openingStats'][0] else None,
                       'pairedSecondWin': compare(baseline, run, 'win', 2),
                       'pairedSecondEnabled': compare(baseline, run, 'fullyEnabledAttack', 2)})
    output.sort(key=lambda r: (r['winRate'] or 0, r['secondOpening']['enabledRateAllGames'] or 0), reverse=True)
    result = {'phase': phase, 'opponent': baseline['opponent'], 'policy': baseline['policy'],
              'seed': baseline['seed'], 'pairs': baseline['pairs'], 'variants': output,
              'uncertainty': 'Approximate 95% paired normal intervals; both starting orders cluster by seed for combined win rate. Screening intervals are exploratory and do not correct for selecting among variants.'}
    (directory / (phase + '-comparison.json')).write_text(json.dumps(result, indent=2) + '\n')
    with (directory / (phase + '-comparison.csv')).open('w', newline='') as file:
        writer = csv.writer(file)
        writer.writerow(['variant', 'games', 'win_rate', 'first_win_rate', 'second_win_rate',
                         'first_enabled_all_games', 'second_any_attack_reached', 'second_goal_attack_reached',
                         'second_enabled_reached', 'second_enabled_all_games', 'second_blender_turn1_all_games', 'paired_win_delta',
                         'paired_win_ci_low', 'paired_win_ci_high', 'paired_second_enabled_delta'])
        for row in output:
            second = row['secondOpening']
            ci = row['pairedWin']['approximate95CI'] or [None, None]
            writer.writerow([row['variant'], row['games'], row['winRate'], row['firstWinRate'], row['secondWinRate'],
                             row['firstOpening']['enabledRateAllGames'], second['attackRateReached'],
                             second['goalAttacks'] / second['reached'] if second['reached'] else None,
                             second['enabledRateReached'], second['enabledRateAllGames'], second['blenderTurn1RateAllGames'], row['pairedWin']['delta'],
                             *ci, row['pairedSecondEnabled']['delta']])
    return result


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('directory', type=Path)
    parser.add_argument('--phase', default='screen')
    args = parser.parse_args()
    # Comparison files are outputs rather than runs; avoid loading them on repeat analysis.
    result = analyze(args.directory, args.phase)
    for row in result['variants']:
        print(f"{row['variant']}: win {row['winRate']:.1%}, second enabled {row['secondOpening']['enabledRateReached']:.1%}, delta win {row['pairedWin']['delta']:+.1%}")
