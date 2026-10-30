#!/usr/bin/env python3
"""Snapshot the menu's selected archetype decks and current bot for a local tournament."""
import argparse
import importlib.util
import json
import pathlib
import re

ROOT = pathlib.Path(__file__).resolve().parents[1]

def setup(destination):
    source = (ROOT / 'SharedUI/Sites/GrandArchiveSim/MainMenu.php').read_text()
    match = re.search(r'var GA_BOT_SAMPLE_DECKS = (\[[\s\S]*?\]);', source)
    decks = json.loads(match[1])
    if len(decks) < 2 or len({d['label'] for d in decks}) != len(decks):
        raise ValueError('Expected unique archetype labels')
    destination.mkdir(parents=True, exist_ok=False)
    config = {'decks': [], 'seeds': [101, 202, 303, 404, 505]}
    for index, deck in enumerate(decks):
        name = f'deck-{index:02d}.txt'
        (destination / name).write_text(deck['text'].strip() + '\n')
        config['decks'].append({'name': deck['label'], 'path': name, 'source': deck.get('source')})
    (destination / 'config.json').write_text(json.dumps(config, indent=2) + '\n')
    spec = importlib.util.spec_from_file_location('evaluation', ROOT / 'DevTools/ga-bot-evaluate.py')
    evaluation = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(evaluation)
    evaluation.freeze(destination / 'baseline')
    total = len(evaluation.make_tournament_schedule(config['decks'], config['seeds']))
    print(f'Snapshotted {len(decks)} archetypes; {total} games in {total // 10} full batches of 10 plus {total % 10} games.')

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('destination', type=pathlib.Path)
    setup(parser.parse_args().destination)
