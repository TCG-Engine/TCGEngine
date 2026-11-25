import json
import tempfile
import unittest
from pathlib import Path
from select_ga_positions import select_positions


class SelectTests(unittest.TestCase):
    def test_pressure_ranking_dedup_and_private_state_independence(self):
        def action(step, hp, keys):
            return {'step': step, 'turnPlayer': 1,
                    'observation': json.dumps({'handler:KindleProcess': 1, 'ownHP': hp}),
                    'actionKeys': [json.dumps(['MZMAYCHOOSE', 100, k]) for k in keys],
                    'actionKey': json.dumps(['MZMAYCHOOSE', 100, keys[0]])}
        replay = {'initialSnapshotHash': 'present', 'initialGamestateText': 'must not be parsed',
                  'actions': [action(1, .5, ['pay', 'PASS']), action(2, .1, ['a', 'b', 'PASS']),
                              action(3, .1, ['PASS', 'PASS'])]}
        with tempfile.TemporaryDirectory() as directory:
            paths = [Path(directory)/name for name in ('game-1.json', 'game-2.json')]
            for path in paths:
                path.write_text(json.dumps(replay))
            result = select_positions(paths, ['Kindle'], 10)
        self.assertEqual([p['step'] for p in result], [2, 1])
        self.assertEqual(result[0]['qualityLabel'], 'unreviewed')
        self.assertNotIn('initialGamestateText', result[0])

    def test_old_captures_excluded(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory)/'game-1.json'
            path.write_text('{}')
            self.assertEqual(select_positions([path], ['Kindle']), [])


if __name__ == '__main__':
    unittest.main()
