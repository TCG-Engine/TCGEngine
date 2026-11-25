import importlib.util
import json
import pathlib
import tempfile
import unittest

spec = importlib.util.spec_from_file_location('evaluation', pathlib.Path(__file__).with_name('ga-bot-evaluate.py'))
evaluation = importlib.util.module_from_spec(spec)
spec.loader.exec_module(evaluation)

class EvaluationTest(unittest.TestCase):
    def test_schedule_pairs_every_deck_and_seed(self):
        schedule = evaluation.make_schedule(['A', 'B', 'C', 'D'], [1, 2, 3, 4, 5])
        self.assertEqual(len(schedule), 160)
        self.assertEqual(len(set(schedule)), 160)
        for i in range(0, len(schedule), 2):
            self.assertEqual(schedule[i][:3], schedule[i + 1][:3])
            self.assertEqual([schedule[i][3], schedule[i + 1][3]], [1, 2])

    def test_round_robin_covers_each_pair_and_swaps_seats(self):
        for count in [4, 5, 21]:
            decks = list(range(count))
            schedule = evaluation.make_tournament_schedule(decks, [101, 202])
            self.assertEqual(len(schedule), count * (count - 1) * 2)
            for seed in [101, 202]:
                pairs = [frozenset((a, b)) for a, b, s, seat in schedule if s == seed and seat == 1]
                self.assertEqual(len(set(pairs)), count * (count - 1) // 2)
                self.assertTrue(all(len(pair) == 2 for pair in pairs))
            for offset in range(0, len(schedule), 10):
                batch = schedule[offset:offset + 10]
                for i in range(0, len(batch), 2):
                    self.assertEqual(batch[i][:3], batch[i+1][:3])
                    self.assertEqual([batch[i][3], batch[i+1][3]], [1, 2])

    def test_freeze_is_immutable_and_isolated(self):
        with tempfile.TemporaryDirectory() as directory:
            target = pathlib.Path(directory) / 'baseline'
            evaluation.freeze(target)
            manifest = json.loads((target / 'manifest.json').read_text())
            for name, digest in manifest['files'].items():
                self.assertEqual(evaluation.digest(target / name), digest)
                self.assertNotIn('GABot', (target / name).read_text())
            with self.assertRaises(FileExistsError): evaluation.freeze(target)

    def test_failures_are_excluded_from_wins(self):
        with tempfile.TemporaryDirectory() as directory:
            game = dict(candidateDeck='A', baselineDeck='B', candidateSeat=2, winner=2, completed=True, exitCode=0, steps=25)
            failed = dict(game, exitCode=1)
            summary = dict(games=[game, failed], scheduled=2, failed=1, candidateWins=1)
            evaluation.write_report(pathlib.Path(directory), summary)
            report = (pathlib.Path(directory) / 'report.md').read_text()
            self.assertIn('Candidate wins: 1/1', report)
            self.assertIn('| A | B | 1 | 1 | 25.0 |', report)
            self.assertIn('Game 0002: exit=1', report)

    def test_rejections_include_completed_and_failed_games(self):
        with tempfile.TemporaryDirectory() as directory:
            game = dict(candidateDeck='A', baselineDeck='B', candidateSeat=2, winner=2,
                        completed=True, exitCode=0, steps=25)
            games = [game, dict(game, rejectedActionAttempts=2),
                     dict(game, completed=False, exitCode=1, rejectedActionAttempts=3)]
            evaluation.write_report(pathlib.Path(directory),
                                    dict(games=games, scheduled=3, failed=1, candidateWins=2))
            report = (pathlib.Path(directory) / 'report.md').read_text()
            self.assertIn('5 attempts across 2 games.', report)
            self.assertIn('Game 0002: 2 attempts', report)
            self.assertIn('Game 0003: 3 attempts', report)

if __name__ == '__main__': unittest.main()
