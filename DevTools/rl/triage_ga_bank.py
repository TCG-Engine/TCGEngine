"""Rank support gaps by distinct source games, preserving review-only provenance."""
import argparse
import json
import re
from pathlib import Path


def summarize(bank):
    groups = {}
    for position in bank.values():
        if position['category'] != 'missing_support':
            continue
        observation = json.loads(position['observation'])
        handlers = [k[8:] for k in observation if k.startswith('handler:')] if isinstance(observation, dict) else []
        handler = handlers[0] if handlers else 'unknown'
        for action in set(position['support'].get('unsupportedActions', [])):
            parts = json.loads(action)
            # Collapse damage/readiness variants, but keep ability slots and modes distinct.
            choice = re.sub(r'((?:my|their)[A-Za-z]+:[A-Za-z0-9]+):\d+:\d+', r'\1', parts[2])
            key = (position['archetype'], handler, parts[0], parts[1], choice)
            group = groups.setdefault(key, {'archetype': key[0], 'handler': handler,
                'decisionType': parts[0], 'mode': parts[1], 'choice': choice,
                'positions': set(), 'games': set(), 'examples': []})
            group['positions'].add(position['id'])
            for example in position['examples']:
                # before/after of one paired game count once, not two independent games.
                path = Path(example['replay'])
                group['games'].add(str(path.parent.parent / path.name))
                if len(group['examples']) < 3:
                    group['examples'].append({'replay': example['replay'], 'step': example['step']})
    rows = [{**g, 'positions': len(g['positions']), 'games': len(g['games'])} for g in groups.values()]
    return sorted(rows, key=lambda g: (-g['games'], -g['positions'], g['archetype'], g['choice']))


if __name__ == '__main__':
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('bank', type=Path); p.add_argument('--output', type=Path, required=True)
    a = p.parse_args()
    rows = summarize(json.loads(a.bank.read_text()))
    a.output.parent.mkdir(parents=True, exist_ok=True)
    a.output.write_text(json.dumps({'gaps': rows, 'note': 'Counts are diagnostic coverage, not causal loss attribution. Unresolved zone indices require replay inspection.'}, indent=2) + '\n')
    print(f'{len(rows)} grouped support gaps')
