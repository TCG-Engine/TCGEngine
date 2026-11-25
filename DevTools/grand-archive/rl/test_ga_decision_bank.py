import unittest
from ga_decision_bank import first_difference, position

class BankTests(unittest.TestCase):
    def test_divergence_and_identity(self):
        a = {'initialGamestateHash': 'same', 'actions': [{'turnPlayer': 1, 'action': {'cardID': 'PASS'}}]}
        b = {'initialGamestateHash': 'same', 'actions': [{'turnPlayer': 1, 'action': {'cardID': 'YES'}}]}
        self.assertEqual(first_difference(a, b), 0)
        self.assertIsNone(first_difference(a, a))
        with self.assertRaises(ValueError): first_difference(a, {**b, 'initialGamestateHash': 'different'})

    def test_bank_public_projection_and_no_automatic_labels(self):
        step = {'observation': 'public', 'actionKeys': ['pass', 'play'], 'legalIndices': [0, 1],
                'actionKey': 'pass', 'decisionSupport': {'status': 'missing'}, 'hiddenDeck': 'SECRET'}
        p = position(step, 'Tristan')
        self.assertEqual(p['category'], 'missing_support')
        self.assertEqual(p['qualityLabel'], 'unreviewed')
        self.assertNotIn('SECRET', str(p))
        step['decisionSupport'] = {'status': 'supported'}
        self.assertEqual(position(step, 'Tristan')['category'], 'decision_review')
        self.assertNotEqual(p['id'], position(step, 'Tristan')['id'])
        # Identical public decisions with different support evidence must not merge.
        self.assertEqual(position(step, 'Tristan')['id'], position(step, 'Tristan')['id'])
        step['decisionSupport'] = None
        self.assertEqual(position(step, 'Tristan')['category'], 'unknown_support')
        self.assertNotEqual(p['id'], position(step, 'Lorraine')['id'])
        step['legalIndices'] = [1]
        self.assertIsNone(position(step, 'Tristan'))
        step['observation'] = None
        self.assertIsNone(position(step, 'Tristan'))

if __name__ == '__main__': unittest.main()
