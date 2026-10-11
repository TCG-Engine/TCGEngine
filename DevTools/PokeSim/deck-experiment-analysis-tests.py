import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location('experiments', Path(__file__).with_name('analyze-deck-experiments.py'))
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class PairedComparisonTests(unittest.TestCase):
    def runs(self):
        baseline = {'seed': 1, 'pairs': 2, 'results': []}
        variant = {'seed': 1, 'pairs': 2, 'results': []}
        for seed in [1, 2]:
            for first in [1, 2]:
                row = {'seed': seed, 'firstPlayer': first, 'status': 'complete', 'winner': 2,
                       'openingStats': [{'player': 1, 'fullyEnabledAttack': False}]}
                baseline['results'].append(row)
                variant['results'].append({**row, 'winner': 1 if seed == 1 else 2,
                                          'openingStats': [{'player': 1, 'fullyEnabledAttack': seed == 1}]})
        return baseline, variant

    def test_both_orders_cluster_by_seed(self):
        result = module.compare(*self.runs(), 'win')
        self.assertEqual(result['pairs'], 2)
        self.assertEqual(result['delta'], .5)
        self.assertAlmostEqual(result['approximate95CI'][0], -.48)
        self.assertEqual(result['approximate95CI'][1], 1)

    def test_incomplete_seat_excludes_entire_seed_for_combined_wins(self):
        baseline, variant = self.runs()
        variant['results'][-1]['status'] = 'capped'
        result = module.compare(baseline, variant, 'win')
        self.assertEqual(result, {'pairs': 1, 'delta': 1, 'approximate95CI': None})
        self.assertEqual(module.compare(baseline, variant, 'win', 1)['pairs'], 2)

    def test_second_openings_and_identical_baseline(self):
        baseline, variant = self.runs()
        self.assertEqual(module.compare(baseline, variant, 'fullyEnabledAttack', 2)['delta'], .5)
        self.assertEqual(module.compare(baseline, baseline, 'win')['delta'], 0)

    def test_mirror_openings_include_subject_seat_only(self):
        opening = {'player': 1, 'deck': 'sinistcha', 'order': 'second', 'goal': 'Vengeful Anchor',
                   'status': 'complete', 'attackDeclared': True, 'attackResolved': True,
                   'goalAttack': True, 'fullyEnabledAttack': True, 'blockers': []}
        run = {'results': [{'status': 'complete', 'winner': 1, 'openingStats': [opening,
                           {**opening, 'player': 2, 'fullyEnabledAttack': False}]}]}
        group = module.subject_openings(run)['second']
        self.assertIsNone(group['blenderTurn1RateAllGames'])
        self.assertEqual(group['games'], 1)
        self.assertEqual(group['enabledRateReached'], 1)
        self.assertEqual(group['conversion']['enabled']['wins'], 1)


    def test_blender_tracks_seat_turn_one_separately_from_enabled(self):
        opening={'player':1,'deck':'dhelmise-v2','order':'second','goal':'Vengeful Anchor',
                 'status':'complete','attackDeclared':False,'attackResolved':False,'goalAttack':False,
                 'fullyEnabledAttack':False,'blockers':[],'blenderTurn1Played':True,'blenderByOpportunityPlayed':True}
        group=module.subject_openings({'results':[{'status':'complete','winner':2,'openingStats':[opening]}]})['second']
        self.assertEqual(group['blenderTurn1RateAllGames'],1)
        self.assertEqual(group['enabledRateAllGames'],0)


if __name__ == '__main__':
    unittest.main()
