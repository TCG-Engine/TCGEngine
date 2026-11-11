import tempfile
from pathlib import Path
import unittest
from ga_opponent_pool import snapshot, verify

class SnapshotTests(unittest.TestCase):
    def test_snapshot_refuses_overwrite_and_detects_tampering(self):
        with tempfile.TemporaryDirectory() as tmp:
            path=Path(tmp)/'v1'
            original=snapshot(path,'candidate-v1')
            self.assertEqual(original,verify(path))
            self.assertIn('candidate',(path/'snapshot.json').read_text())
            with self.assertRaises(FileExistsError):snapshot(path,'overwrite')
            target=path/'BotDecisions.php';target.write_text(target.read_text()+'\n// changed\n')
            with self.assertRaisesRegex(ValueError,'Snapshot changed'):verify(path)

    def test_rejects_unexpected_manifest_paths(self):
        import json
        with tempfile.TemporaryDirectory() as tmp:
            path=Path(tmp);(path/'manifest.json').write_text(json.dumps({'files':{'../elsewhere':'hash'}}))
            with self.assertRaisesRegex(ValueError,'four chooser'):verify(path)

if __name__=='__main__':unittest.main()
