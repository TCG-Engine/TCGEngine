import copy
import unittest
from compare_ga_evaluations import compare


class ComparisonTest(unittest.TestCase):
    def report(self):
        return dict(mode='evaluation', initialPolicyHash='a', finalPolicyHash='a',
                    start=0, seed=1000001, deckHashes=['a', 'b'], baseline={},
                    sourceHashes={'engine': 'hash'}, maxSteps=1000, learnerWins=0,
                    games=[dict(seed=1000001, learnerSeat=1, learnerDeck='a',
                                opponentDeck='b', completed=True, learnerReward=-1)])

    def test_paired_improvement(self):
        a = self.report()
        b = copy.deepcopy(a)
        b.update(initialPolicyHash='b', finalPolicyHash='b', learnerWins=1)
        b['games'][0]['learnerReward'] = 1
        self.assertEqual(compare(a, b)['improvedOutcomes'], 1)
        self.assertEqual(compare(b, a)['regressedOutcomes'], 1)
        self.assertEqual(compare(a, a)['unchangedOutcomes'], 1)

    def test_incomplete_pair_excluded(self):
        a, b = self.report(), self.report()
        b['games'][0].update(completed=False, learnerReward=None)
        result = compare(a, b)
        self.assertEqual(result['pairedCompletedGames'], 0)
        self.assertEqual(result['excludedIncompletePairs'], 1)

    def test_incomparable_reports_rejected(self):
        a = self.report()
        for key, value in [('seed', 12), ('sourceHashes', {}), ('maxSteps', 2),
                           ('mode', 'training'), ('finalPolicyHash', 'changed')]:
            with self.subTest(key=key):
                b = self.report()
                b[key] = value
                with self.assertRaises(ValueError):
                    compare(a, b)
        b = self.report()
        b['games'][0]['learnerSeat'] = 2
        with self.assertRaises(ValueError):
            compare(a, b)


if __name__ == '__main__':
    unittest.main()
