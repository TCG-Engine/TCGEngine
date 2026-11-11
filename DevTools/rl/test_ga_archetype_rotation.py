import tempfile
import unittest
from pathlib import Path
from ga_archetype_rotation import check, write, digest, compare_rows

class RotationTests(unittest.TestCase):
    def test_pinned_input_mutation_rejected(self):
        with tempfile.TemporaryDirectory() as tmp:
            p = Path(tmp); (p/'deck.txt').write_text('original')
            write(p/'batch.json', {'inputHashes': {'deck.txt': digest(p/'deck.txt')}})
            check(p)
            (p/'deck.txt').write_text('changed')
            with self.assertRaisesRegex(ValueError, 'Pinned'): check(p)

    def test_caps_and_regression_are_visible(self):
        old = dict(archetype='Alice', opponentVersion='historical', scheduled=40, completed=40, wins=20)
        result = compare_rows([old], [{**old, 'completed':39, 'wins':19}])[0]
        self.assertFalse(result['complete'])
        self.assertEqual(result['deltaWins'], -1)

    def test_missing_or_duplicate_opponent_rejected(self):
        row = dict(archetype='Alice', opponentVersion='historical', scheduled=40, completed=40, wins=20)
        for new in ([], [row, row], [{**row, 'opponentVersion':'other'}]):
            with self.assertRaises(ValueError): compare_rows([row], new)

if __name__ == '__main__': unittest.main()
