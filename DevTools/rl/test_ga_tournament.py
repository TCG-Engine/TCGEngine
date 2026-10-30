"""Integration checks for incomplete-game exclusion and evaluation isolation."""
import json
import pathlib
import subprocess
import tempfile
import unittest

ROOT = pathlib.Path(__file__).resolve().parents[2]

class TournamentTest(unittest.TestCase):
    def test_incomplete_games_never_train_and_evaluation_is_frozen(self):
        with tempfile.TemporaryDirectory(prefix='ga-rl-test-') as folder:
            base = pathlib.Path(folder)
            subprocess.run(['python3', str(ROOT/'DevTools/ga-bot-evaluate.py'), 'freeze', str(base/'baseline')], check=True, capture_output=True)
            deck = str(ROOT/'DevTools/rl/benchmark_control_ga.txt')
            config = base/'config.json'
            config.write_text(json.dumps({'decks': [{'name': 'A', 'path': deck}, {'name': 'B', 'path': deck}]}))
            common = ['php', str(ROOT/'DevTools/rl/train_ga_tournament.php'), '--config', str(config), '--baseline', str(base/'baseline'), '--games', '2', '--max-steps', '1']
            subprocess.run(common + ['--output', str(base/'train')], check=True, capture_output=True)
            report = json.loads((base/'train/report.json').read_text())
            self.assertEqual(report['completed'], 0)
            checkpoint = json.loads((base/'train/latest.json').read_text())
            self.assertEqual(checkpoint['state_key_version'], 'GrandArchiveSim:linear-v1')
            self.assertEqual(report['initialPolicyHash'], report['finalPolicyHash'])
            self.assertEqual([g['learnerSeat'] for g in report['games']], [1, 2])
            for i, seat in [(1, 1), (2, 2)]:
                replay = json.loads((base/f'train/game-{i}.json').read_text())
                self.assertTrue(replay['result']['timedOut'])
                for action in replay['actions']:
                    self.assertEqual(action['trainingPlayer'], action['turnPlayer'] == seat)
            subprocess.run(common + ['--output', str(base/'eval'), '--checkpoint', str(base/'train/latest.json'), '--evaluate'], check=True, capture_output=True)
            evaluated = json.loads((base/'eval/report.json').read_text())
            self.assertEqual(evaluated['initialPolicyHash'], evaluated['finalPolicyHash'])
            self.assertNotEqual(report['seed'], evaluated['seed'])
            mismatch = subprocess.run(common + ['--output', str(base/'mismatch'), '--checkpoint', str(base/'train/latest.json'), '--representation', 'public-v1'], capture_output=True)
            self.assertNotEqual(mismatch.returncode, 0)
            subprocess.run(common + ['--output', str(base/'imitation'), '--imitate'], check=True, capture_output=True)
            imitated = json.loads((base/'imitation/report.json').read_text())
            self.assertEqual(imitated['mode'], 'imitation')
            self.assertEqual(imitated['initialPolicyHash'], imitated['finalPolicyHash'])
            for i in (1, 2):
                replay = json.loads((base/f'imitation/game-{i}.json').read_text())
                for action in replay['actions']:
                    if action['trainingPlayer']:
                        self.assertEqual(action['heuristicPolicy'], 'ga-teacher')
                        self.assertFalse(action['heuristicCovered'])
            invalid = subprocess.run(common + ['--output', str(base/'invalid'), '--imitate', '--evaluate', '--checkpoint', str(base/'train/latest.json')], capture_output=True)
            self.assertNotEqual(invalid.returncode, 0)
            subprocess.run(common + ['--output', str(base/'linear'), '--representation', 'linear-v1'], check=True, capture_output=True)
            linear = json.loads((base/'linear/latest.json').read_text())
            self.assertEqual(linear['state_key_version'], 'GrandArchiveSim:linear-v1')
            self.assertFalse(linear['logits'])
            subprocess.run(common + ['--output', str(base/'linear-eval'), '--checkpoint', str(base/'linear/latest.json'), '--evaluate'], check=True, capture_output=True)
            linear_eval = json.loads((base/'linear-eval/report.json').read_text())
            self.assertEqual(linear_eval['initialPolicyHash'], linear_eval['finalPolicyHash'])
            collision = subprocess.run(common + ['--output', str(base/'train')], capture_output=True)
            self.assertNotEqual(collision.returncode, 0)

if __name__ == '__main__':
    unittest.main()
