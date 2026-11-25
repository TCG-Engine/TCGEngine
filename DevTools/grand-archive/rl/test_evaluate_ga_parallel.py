"""Real-engine checks for serial/parallel equivalence and safe cache reuse."""
import json
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch

import run_ga_learning_cycle

from evaluate_ga_parallel import evaluate, identity, cache_valid
from run_ga_learning_cycle import screen_passes
from compare_ga_evaluations import compare

ROOT = Path(__file__).resolve().parents[3]


class EvaluationTest(unittest.TestCase):
    def test_gate(self):
        good = dict(excludedIncompletePairs=0, regressedOutcomes=0, pairedAfterWins=2, pairedBeforeWins=2)
        self.assertTrue(screen_passes(good))
        for change in ({'excludedIncompletePairs':1}, {'regressedOutcomes':1}, {'pairedAfterWins':1}):
            self.assertFalse(screen_passes({**good, **change}))

    def test_successful_cycle_and_failure_summary(self):
        with tempfile.TemporaryDirectory(prefix='ga-cycle-test-') as temp:
            folder = Path(temp)
            checkpoint = folder/'checkpoint.json'
            checkpoint.write_text('{}')
            calls = []

            def fake_evaluate(config, baseline, checkpoint, output, games, seed, *rest):
                output.mkdir()
                calls.append(seed)
                report = dict(mode='evaluation', initialPolicyHash='same', finalPolicyHash='same',
                              start=0, seed=seed, deckHashes=['a','b'], baseline={}, sourceHashes={},
                              maxSteps=1000, learnerWins=0, games=[dict(seed=seed,learnerSeat=1,
                              learnerDeck='a',opponentDeck='b',completed=True,learnerReward=-1,
                              initialGamestateHash='same')])
                (output/'report.json').write_text(json.dumps(report))
                return {'cacheHit':False,'elapsedSeconds':0}

            argv = ['cycle','--config','config','--baseline','baseline',
                    '--teacher-checkpoint',str(checkpoint),'--candidate-checkpoint',str(checkpoint),
                    '--output',str(folder/'success')]
            with patch('sys.argv',argv), patch.object(run_ga_learning_cycle,'evaluate',side_effect=fake_evaluate):
                run_ga_learning_cycle.main()
            summary=json.loads((folder/'success/summary.json').read_text())
            self.assertEqual(summary['status'],'evaluated')
            self.assertEqual(calls,[9100001,9100001,9200001,9200001,9300001,9300001])
            self.assertFalse(summary['promoted'])
            argv[-1]=str(folder/'failure')
            with patch('sys.argv',argv), patch.object(run_ga_learning_cycle,'evaluate',side_effect=ValueError('bad start')):
                with self.assertRaises(ValueError):
                    run_ga_learning_cycle.main()
            summary=json.loads((folder/'failure/summary.json').read_text())
            self.assertEqual(summary['status'],'failed')
            self.assertEqual(summary['error'],'bad start')

    def test_parallel_cache_and_cycle(self):
        with tempfile.TemporaryDirectory(prefix='ga-parallel-test-') as temp:
            folder = Path(temp)
            baseline = folder/'baseline'
            subprocess.run(['python3', str(ROOT/'DevTools/grand-archive/ga-bot-evaluate.py'), 'freeze', str(baseline)], check=True, capture_output=True)
            deck = folder/'deck.txt'
            deck.write_text((ROOT/'DevTools/grand-archive/rl/benchmark_control_ga.txt').read_text().replace(
                '60 Esteemed Knight', '30 Esteemed Knight\n30 Weaving Manastream'))
            config = folder/'config.json'
            config.write_text(json.dumps({'decks':[{'name':'A','path':'deck.txt'},{'name':'B','path':'deck.txt'}]}))
            subprocess.run(['php', str(ROOT/'DevTools/grand-archive/rl/train_ga_tournament.php'), '--config',str(config),
                            '--baseline',str(baseline),'--output',str(folder/'train'),'--games','2','--max-steps','1'],
                           check=True,capture_output=True)
            checkpoint = folder/'train/latest.json'
            args = dict(config=config,baseline=baseline,checkpoint=checkpoint,games=4,seed=9900001,max_steps=4)
            evaluate(**args,output=folder/'serial',workers=1)
            meta = evaluate(**args,output=folder/'parallel',workers=2,cache=folder/'cache')
            self.assertFalse(meta['cacheHit'])
            reports = [json.loads((folder/name/'report.json').read_text()) for name in ('serial','parallel')]
            comparison = compare(*reports)
            self.assertEqual(comparison['scheduledGames'],4)
            for a,b in zip(*[r['games'] for r in reports]):
                for key in ('initialGamestateHash','winner','steps','terminationReason','learnerSeat','seed'):
                    self.assertEqual(a[key],b[key],key)
                replays = [json.loads((folder/name/f"game-{a['episode']}.json").read_text()) for name in ('serial','parallel')]
                self.assertEqual(replays[0]['actions'],replays[1]['actions'])
            cached = evaluate(**args,output=folder/'cached',workers=1,cache=folder/'cache')
            self.assertTrue(cached['cacheHit'])
            self.assertEqual((folder/'cached/report.json').read_bytes(),(folder/'parallel/report.json').read_bytes())
            expected = identity(config,baseline,checkpoint,4,9900001,4)
            entry = folder/'cache'/meta['cacheKey']
            self.assertTrue(cache_valid(entry,expected))
            (entry/'game-1.json').write_text('{}')
            self.assertFalse(cache_valid(entry,expected))
            repaired = evaluate(**args,output=folder/'repaired',workers=2,cache=folder/'cache')
            self.assertFalse(repaired['cacheHit'])
            self.assertTrue(cache_valid(entry,expected))
            for path in (deck, checkpoint, baseline/'BotLegalActions.php'):
                original = path.read_bytes()
                path.write_bytes(original+b'\n')
                self.assertNotEqual(expected,identity(config,baseline,checkpoint,4,9900001,4))
                path.write_bytes(original)
            self.assertNotEqual(expected,identity(config,baseline,checkpoint,4,9900002,4))
            self.assertNotEqual(expected,identity(config,baseline,checkpoint,4,9900001,5))
            subprocess.run(['python3',str(ROOT/'DevTools/grand-archive/rl/run_ga_learning_cycle.py'),
                            '--config',str(config),'--baseline',str(baseline),'--output',str(folder/'cycle'),
                            '--teacher-checkpoint',str(checkpoint),'--candidate-checkpoint',str(checkpoint),
                            '--screen-games','2','--evaluation-games','2','--max-steps','1',
                            '--cache',str(folder/'cache')],check=True,capture_output=True)
            summary=json.loads((folder/'cycle/summary.json').read_text())
            self.assertEqual(summary['status'],'screen_rejected')
            self.assertFalse(summary['promoted'])
            self.assertFalse(list((folder/'cycle').glob('evaluation-*')))


if __name__ == '__main__':
    unittest.main()
