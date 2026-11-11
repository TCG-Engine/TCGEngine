import unittest
from triage_ga_bank import summarize

class TriageTest(unittest.TestCase):
    def test_counts_pairs_once_and_preserves_ability(self):
        def item(i, status, side):
            return {'id':i,'archetype':'Tristan','category':'missing_support',
                    'observation':'{"handler:response":1}',
                    'support':{'unsupportedActions':[f'["MZMAYCHOOSE",100,"myField:abc:{status}:0@Activate-0","","",[]]']},
                    'examples':[{'replay':f'/round/0/{side}/game-1.json','step':3}]}
        rows=summarize({'a':item('a',1,'before'),'b':item('b',2,'after')})
        self.assertEqual(len(rows),1)
        self.assertEqual(rows[0]['games'],1)
        self.assertEqual(rows[0]['positions'],2)
        self.assertEqual(rows[0]['choice'],'myField:abc@Activate-0')

if __name__=='__main__':unittest.main()
