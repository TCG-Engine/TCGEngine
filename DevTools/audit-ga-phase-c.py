#!/usr/bin/env python3
"""Rebuild Phase C's source inventory; classifications are review buckets, not verdicts.

Generated handlers are listed even when maintained code assigns the same key:
that assignment may wrap/delegate rather than replace the generated body.
"""
import csv
import io
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'DevTools/ga-fixtures'
FILES = sorted((ROOT / 'GrandArchiveSim/Custom').glob('*.php')) + [
    ROOT / 'GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php'
]
DECL = re.compile(r'^function\s+(\w+)\s*\(|^\$(\w+)\["([^"]+)"\]\s*=\s*function')
ELEMENT = re.compile(r'\b(?:CardElement|EffectiveCardElement)\s*\(')
COMBAT = re.compile(r'GetVariable\("Combat(?:Attacker|Target)"\)')


def bucket(line):
    if 'PropertyContains(' in line:
        return 'membership'
    if re.search(r'(?:===|!==|==|!=)\s*"NORM"', line):
        return 'Norm predicate: membership versus Norm-only'
    if re.search(r'(?:===|!==|==|!=)\s*"(?:FIRE|WATER|WIND)"', line):
        return 'basic-element equality: membership candidate'
    if re.search(r'(?:===|!==|==|!=)\s*"[A-Z]+"', line):
        return 'advanced-element equality: review effective overrides separately'
    if re.search(r'CanPlayer.*Element', line):
        return 'element requirements: preserve separate semantics'
    if 'in_array(' in line or 'distinctElements[' in line:
        return 'set/distinct-element comparison: separate rules review'
    return 'value/local: inspect downstream comparisons in enclosing handler'


rows = {'element': [], 'combat': [], 'material': []}
for path in FILES:
    owner = 'file scope'
    lines = path.read_text().splitlines()
    for index, line in enumerate(lines):
        decl = DECL.match(line)
        if decl:
            owner = decl[1] or f'${decl[2]}["{decl[3]}"]'
        if line.lstrip().startswith(('//', '*', '/*')):
            continue
        location = f'{path.relative_to(ROOT)}:{index + 1}'
        code = line.strip()
        if ELEMENT.search(line):
            rows['element'].append([location, owner, bucket(line), code])
        if COMBAT.search(line):
            rows['combat'].append([location, owner, 'raw stored reference: caller perspective/context must be checked', code])
        if re.search(r'GetMaterial\(|(?:my|their)Material|["\']Material["\']', line):
            kind = 'movement' if 'MZMove(' in line else 'selection/query/context'
            rows['material'].append([location, owner, kind, code])

for name, entries in rows.items():
    output = io.StringIO()
    writer = csv.writer(output, delimiter='\t', lineterminator='\n')
    writer.writerow(['location', 'nearest_declaration', 'review_bucket', 'source'])
    writer.writerows(entries)
    (OUT / f'phase-c-{name}-inventory.tsv').write_text(output.getvalue())
    print(f'{name}: {len(entries)} source sites (not a live-defect count)')
