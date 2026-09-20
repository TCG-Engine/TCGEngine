#!/usr/bin/env python3
"""Round-robin pairs for value-model COLLECTION (spec 2026-09-19 §6): every ordered pair of distinct gate fixtures,
each deck under its own '# Style:' label, seeds v<first>..v<last>. Seeds 'v…' never overlap the A/B seeds 's…'.
    python3 SWUSim/DevTools/rl/value_pairs.py 1 40 > /tmp/value_pairs.tsv
Output TSV: deckA-path  styleA  deckB-path  styleB  seed"""
import glob, os, sys
D = 'SWUSim/Tests/BotFixtures/meta-2026-09'
def style(p):
    for line in open(p):
        if line.startswith('# Style:'):
            return line.split()[2]
    sys.exit(f'no # Style: in {p}')
decks = [(p, style(p)) for p in sorted(glob.glob(D + '/*.txt'))]
for i in range(int(sys.argv[1]), int(sys.argv[2]) + 1):
    for a, sa in decks:
        for b, sb in decks:
            if a != b:
                print('\t'.join((a, sa, b, sb, f'v{i:04d}')))
