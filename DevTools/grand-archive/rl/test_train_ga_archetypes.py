import copy
import json
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch

from train_ga_archetypes import ROOT, accepted, merge, run_round, run_stage, validate_candidate


class ArchetypeTrainingTest(unittest.TestCase):
    def base(self):
        return {'state_key_version': 'GrandArchiveSim:linear-v1', 'logits': {
            '__linear__': {'bias': 1.0}, '__archetype__:A': {'bias': .1}, '__archetype__:B': {'bias': -.1}}}

    def test_merge_preserves_foundation_rejected_and_unseen_archetypes(self):
        base = self.base()
        a, b = copy.deepcopy(base), copy.deepcopy(base)
        a['logits']['__archetype__:A']['bias'] = .3
        b['logits']['__archetype__:B']['bias'] = -.4
        result = merge(base, [('A', a, True), ('B', b, False)])
        self.assertEqual(result['logits']['__linear__'], base['logits']['__linear__'])
        self.assertEqual(result['logits']['__archetype__:A']['bias'], .3)
        self.assertEqual(result['logits']['__archetype__:B'], base['logits']['__archetype__:B'])
        for bucket in ('__linear__', '__archetype__:B'):
            bad = copy.deepcopy(a)
            bad['logits'][bucket]['bias'] += 1
            with self.assertRaises(ValueError):
                validate_candidate(base, bad, 'A')
        bad = copy.deepcopy(a); bad['temperature'] = 2
        with self.assertRaises(ValueError):
            merge(base, [('A', bad, False)])  # Invalid even if rejected.
        with self.assertRaises(ValueError):
            merge(base, [('A', a, True), ('A', a, True)])

    def test_acceptance_requires_complete_improvement_without_regression(self):
        c = dict(excludedIncompletePairs=0, regressedOutcomes=0, improvedOutcomes=1,
                 pairedAfterWins=2, pairedBeforeWins=1)
        training = {'games': [{'completed': True}]}
        self.assertTrue(accepted(c, training))
        for key, value in [('excludedIncompletePairs',1), ('regressedOutcomes',1),
                           ('improvedOutcomes',0), ('pairedAfterWins',1)]:
            self.assertFalse(accepted({**c, key:value}, training))
        self.assertFalse(accepted(c, {'games':[{'completed':False}]}))

    def test_interrupted_stage_preserved_and_retried(self):
        with tempfile.TemporaryDirectory() as temp:
            folder = Path(temp)/'train'; folder.mkdir(); (folder/'partial').write_text('diagnostic')
            with patch('train_ga_archetypes.subprocess.run', side_effect=RuntimeError('interrupted')):
                with self.assertRaises(RuntimeError):
                    run_stage(folder, ['unused'], {})
            self.assertFalse(folder.exists())
            self.assertEqual(len(list(Path(temp).glob('train.interrupted-*/partial'))), 1)
            self.assertFalse(folder.with_suffix('.complete.json').exists())

    def test_real_workers_route_resume_and_reject_incomplete_round(self):
        with tempfile.TemporaryDirectory(prefix='ga-archetypes-test-') as temp:
            folder = Path(temp)
            subprocess.run(['python3', str(ROOT/'DevTools/grand-archive/ga-bot-evaluate.py'), 'freeze', str(folder/'baseline')],
                           check=True, capture_output=True)
            config = folder/'config.json'
            config.write_text(json.dumps({'decks': [dict(name='Same champion '+key, archetypeId=key,
                path=str(ROOT/'DevTools/grand-archive/rl/benchmark_control_ga.txt')) for key in ('A','B','C')]}))
            checkpoint = folder/'base.json'
            checkpoint.write_text(json.dumps({'state_key_version':'GrandArchiveSim:linear-v1',
                'action_key_version':'ga-semantic-v1','max_actions':4096,'temperature':1,
                'learning_rate':.05,'policy_role':'full','heuristic_policy':'none',
                'logits_format':'sparse_action_key_map','logits':{'__linear__':{'act:PASS':.1}}}))
            kwargs = dict(config=config, baseline=folder/'baseline', checkpoint=checkpoint, output=folder/'round',
                          workers=2, training_games=4, screen_games=4, max_steps=1)
            result = run_round(**kwargs)
            self.assertEqual(result['status'],'completed')
            self.assertEqual(result['acceptedArchetypes'],0)
            self.assertFalse(result['promoted'])
            merged = json.loads((folder/'round/candidate.json').read_text())
            self.assertEqual(merged['logits'], json.loads(checkpoint.read_text())['logits'])
            for archetype, decision in result['archetypes'].items():
                p = Path(decision['artifacts'])
                report = json.loads((p/'train/report.json').read_text())
                self.assertEqual([g['learnerArchetype'] for g in report['games']], [archetype]*4)
                self.assertEqual([g['learnerSeat'] for g in report['games']], [1,2,1,2])
                self.assertEqual(len({g['opponentDeck'] for g in report['games']}),2)
                self.assertEqual(decision['comparison']['excludedIncompletePairs'],4)
            serial = run_round(**{**kwargs, 'output': folder/'serial', 'workers':1})
            self.assertEqual(serial['candidateHash'], result['candidateHash'])
            for archetype, decision in result['archetypes'].items():
                parallel_path = Path(decision['artifacts'])
                serial_path = Path(serial['archetypes'][archetype]['artifacts'])
                for stage in ('train','before','after'):
                    for episode in range(1,5):
                        a = json.loads((parallel_path/stage/f'game-{episode}.json').read_text())
                        b = json.loads((serial_path/stage/f'game-{episode}.json').read_text())
                        self.assertEqual(a['initialGamestateHash'], b['initialGamestateHash'])
                        self.assertEqual([x['action'] for x in a['actions']], [x['action'] for x in b['actions']])
            resumed = run_round(**kwargs, resume=True)
            self.assertEqual(resumed['candidateHash'], result['candidateHash'])
            for r in resumed['archetypes'].values():
                self.assertEqual(r['reusedStages'], ['train','before','after'])
            # Completed data corruption fails closed, without replacing the prior candidate.
            victim = folder/'round/archetype-00/train/latest.json'
            original = victim.read_text(); victim.write_text('{}')
            with self.assertRaises(RuntimeError):
                run_round(**kwargs, resume=True)
            victim.write_text(original)
            checkpoint.write_text(checkpoint.read_text()+' ')
            with self.assertRaises(ValueError):
                run_round(**kwargs, resume=True)
            self.assertEqual(json.loads((folder/'round/candidate.json').read_text()),merged)


if __name__ == '__main__':
    unittest.main()
