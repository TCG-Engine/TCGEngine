import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch
from compare_ga_position import compare_position, compare_seeds


class PositionTests(unittest.TestCase):
    def test_multi_seed_keeps_unfinished_separate_from_losses(self):
        def run(replay, step, horizon, seed):
            return {'replayHash': 'same', 'position': {'seat': 2, 'actionKeys': ['pay'], 'choices': [{'cardID': 'myDiscard-0'}]},
                    'branches': [{'steps': 10, 'terminal': {'isTerminal': seed != 3, 'winner': seed}}],
                    'limitations': 'bounded'}
        with patch('compare_ga_position.compare_position', side_effect=run):
            result = compare_seeds('unused', 1, 10, [1, 2, 3])
        item = result['summary'][0]
        self.assertEqual((item['wins'], item['losses'], item['unfinished']), (1, 1, 1))
        self.assertEqual(result['qualityLabel'], 'unreviewed')

    def test_duplicate_seeds_rejected(self):
        with self.assertRaises(ValueError):
            compare_seeds('unused', 1, 10, [1, 1])

    def test_drift_between_seeds_rejected(self):
        with patch('compare_ga_position.compare_position', side_effect=[
                {'replayHash': 'a', 'position': {}}, {'replayHash': 'b', 'position': {}}]):
            with self.assertRaisesRegex(RuntimeError, 'between seeds'):
                compare_seeds('unused', 1, 10, [1, 2])

    def test_branches_are_isolated_and_unlabelled(self):
        initial = {'positionHash':'same', 'observation':'public', 'actionKeys':['pass','pay'], 'choices':[{'cardID':'PASS'}, {'cardID':'myDiscard-0'}]}
        calls = []
        def run(cmd, **kwargs):
            calls.append(cmd)
            payload = initial if len(calls) == 1 else {**initial, 'branch':{'choice':len(calls)-2}}
            return subprocess.CompletedProcess(cmd, 0, json.dumps(payload), '')
        with tempfile.TemporaryDirectory() as directory:
            replay = Path(directory)/'replay.json'; replay.write_text('{}')
            with patch('compare_ga_position.subprocess.run', side_effect=run):
                result = compare_position(replay, 3, 10, 17)
        self.assertEqual(len(calls), 3)
        self.assertIn('--choice=0', calls[1]); self.assertIn('--choice=1', calls[2])
        self.assertEqual(result['qualityLabel'], 'unreviewed')

    def test_position_drift_is_rejected(self):
        initial = {'positionHash':'same', 'observation':'public', 'actionKeys':['pass'], 'choices':[{'cardID':'PASS'}]}
        responses = [initial, {**initial, 'positionHash':'different', 'branch':{}}]
        with tempfile.TemporaryDirectory() as directory:
            replay = Path(directory)/'replay.json'; replay.write_text('{}')
            with patch('compare_ga_position.subprocess.run', side_effect=[subprocess.CompletedProcess([],0,json.dumps(r),'') for r in responses]):
                with self.assertRaisesRegex(RuntimeError, 'positionHash'):
                    compare_position(replay, 1)

    def test_identical_semantic_keys_preserve_distinct_targets(self):
        initial = {'positionHash': 'same', 'observation': 'public',
                   'actionKeys': ['same-card', 'same-card'],
                   'choices': [{'cardID': 'theirField-0'}, {'cardID': 'theirField-3'}]}
        responses = [initial, {**initial, 'branch': {'choice': 0}},
                     {**initial, 'branch': {'choice': 1}}]
        with tempfile.TemporaryDirectory() as directory:
            replay = Path(directory)/'replay.json'; replay.write_text('{}')
            with patch('compare_ga_position.subprocess.run', side_effect=[
                    subprocess.CompletedProcess([], 0, json.dumps(r), '') for r in responses]):
                result = compare_position(replay, 1)
        self.assertEqual(len(result['branches']), 2)
        self.assertNotEqual(*result['position']['choices'])

    def test_target_drift_rejected_even_when_semantic_keys_match(self):
        initial = {'positionHash': 'same', 'observation': 'public',
                   'actionKeys': ['same-card'], 'choices': [{'cardID': 'theirField-0'}]}
        changed = {**initial, 'choices': [{'cardID': 'theirField-3'}], 'branch': {}}
        with tempfile.TemporaryDirectory() as directory:
            replay = Path(directory)/'replay.json'; replay.write_text('{}')
            with patch('compare_ga_position.subprocess.run', side_effect=[
                    subprocess.CompletedProcess([], 0, json.dumps(r), '') for r in [initial, changed]]):
                with self.assertRaisesRegex(RuntimeError, 'choices'):
                    compare_position(replay, 1)

    def test_corrupt_snapshot_refused_by_worker(self):
        with tempfile.TemporaryDirectory() as directory:
            replay = Path(directory)/'replay.json'
            replay.write_text(json.dumps({'actions':[{}], 'initialGamestateText':'corrupt', 'initialSnapshotHash':'wrong'}))
            worker = Path(__file__).with_name('ga_position_worker.php')
            result = subprocess.run(['php',str(worker),'--replay='+str(replay),'--step=1'],capture_output=True,text=True)
        self.assertNotEqual(result.returncode,0)
        self.assertIn('snapshot hash',result.stderr)

if __name__ == '__main__': unittest.main()
