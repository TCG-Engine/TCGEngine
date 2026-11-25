"""Summarize incomplete games without treating step limits as engine defects."""
import argparse
import json
from collections import Counter
from pathlib import Path

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('batch', type=Path)
a = p.parse_args()
report = json.loads((a.batch/'report.json').read_text())
rows = []
for game in report['games']:
    if game['completed']:
        continue
    replay = json.loads((a.batch/f"game-{game['episode']}.json").read_text())
    diagnostic = replay.get('stuckDiagnostics', {})
    rows.append({'episode':game['episode'],'deck':game['learnerDeck'],
                 'reason':game['terminationReason'],
                 'classification':diagnostic.get('classification','legacy_unclassified'),
                 'stateChangeRate':diagnostic.get('stateChangeRate'),
                 'topActions':diagnostic.get('topActions', [])[:3]})
print(json.dumps({'incomplete':len(rows),'classes':dict(Counter(r['classification'] for r in rows)),
                  'games':rows,'note':'Changing-state limits require replay inspection; never award an RL loss for an incomplete game.'},indent=2))
