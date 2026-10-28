#!/usr/bin/env python3
"""Freeze GA chooser policies or evaluate paired, seeded matches. Python standard library only."""
import argparse, hashlib, json, pathlib, re, subprocess, sys
ROOT = pathlib.Path(__file__).resolve().parents[1]
FILES = ['BotKnowledge.php', 'BotDecisions.php', 'BotLegalActions.php', 'BotHeuristic.php']

def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()

def freeze(destination):
    destination.mkdir(parents=True, exist_ok=False)
    hashes = {}
    for name in FILES:
        source = (ROOT / 'GrandArchiveSim' / name).read_text()
        # Isolate all policy helpers, callback strings, and diagnostic globals together.
        source = re.sub(r'\bGABot', 'GAFrozenBot', source)
        target = destination / name
        target.write_text(source)
        hashes[name] = digest(target)
    (destination / 'manifest.json').write_text(json.dumps({'files': hashes, 'scope': 'Chooser policies only; current engine and legal enumeration are shared'}, indent=2))

def write_report(output, summary):
    games = summary['games']
    valid = [g for g in games if g['completed'] and g['exitCode'] == 0]
    lines = ['# Grand Archive bot evaluation', '',
             f"Played {len(games)}/{summary['scheduled']}; valid {len(valid)}; failed {summary['failed']}.",
             f"Candidate wins: {summary['candidateWins']}/{len(valid)} completed, passing games.", '',
             'These are chooser comparisons under the same engine and legal-action enumeration.',
             'Coverage counts chooser attempts (including retries), not successfully resolved activations.', '',
             '| Candidate deck | Baseline deck | Wins | Valid games | Mean steps |',
             '| --- | --- | ---: | ---: | ---: |']
    for a, b in sorted({(g['candidateDeck'], g['baselineDeck']) for g in games}):
        subset = [g for g in valid if (g['candidateDeck'], g['baselineDeck']) == (a, b)]
        wins = sum(g['winner'] == g['candidateSeat'] for g in subset)
        mean = round(sum(g['steps'] for g in subset) / len(subset), 1) if subset else '-'
        lines.append(f'| {a} | {b} | {wins} | {len(subset)} | {mean} |')
    lines += ['', '| Policy | Chooser calls | Mean decision ms | Slowest game p95 ms |', '| --- | ---: | ---: | ---: |']
    for policy in ['candidate', 'baseline']:
        times = [g['timing'][policy] for g in games if policy in g.get('timing', {})]
        calls = sum(t['calls'] for t in times)
        if calls:
            mean = sum(t['meanMs'] * t['calls'] for t in times) / calls
            lines.append(f"| {policy} | {calls} | {mean:.3f} | {max(t['p95Ms'] for t in times):.3f} |")
    coverage = {}
    for game in games:
        for row in game.get('coverage', []):
            key = tuple(row[k] for k in ['policy', 'type', 'handler', 'card', 'ability'])
            counts = coverage.setdefault(key, dict.fromkeys(['offered', 'selected', 'declined', 'unsupported'], 0))
            for field in counts: counts[field] += row[field]
    lines += ['', '## Optional ability coverage', '', '| Policy | Card / handler / ability | Offered | Selected | Declined | Unsupported |', '| --- | --- | ---: | ---: | ---: | ---: |']
    for key, counts in sorted(coverage.items(), key=lambda item: -item[1]['unsupported']):
        label = ' / '.join(str(x) for x in key[2:] if x is not None).replace('|', '/').replace('\n', ' ')
        lines.append('| ' + key[0] + ' | ' + label + ' | ' + ' | '.join(str(counts[k]) for k in counts) + ' |')
    lines += ['', '## Failed games', '']
    for i, game in enumerate(games, 1):
        if not game['completed'] or game['exitCode'] != 0:
            lines.append(f"- Game {i:04d}: exit={game['exitCode']}; inspect {i:04d}.log and {i:04d}.json.")
    (output / 'report.md').write_text('\n'.join(lines) + '\n')

def make_schedule(decks, seeds):
    return [(a, b, seed, seat) for a in decks for b in decks for seed in seeds for seat in [1, 2]]

def run(args):
    if args.max_steps < 1 or args.timeout <= 0: raise ValueError('Step and time limits must be positive')
    config_path = args.config.resolve()
    config = json.loads(config_path.read_text())
    decks = [(d['name'], (config_path.parent / d['path']).resolve()) for d in config['decks']]
    if not config['seeds'] or any(type(seed) is not int or seed < 0 for seed in config['seeds']):
        raise ValueError('Seeds must be a nonempty list of nonnegative integers')
    if not decks or len({n for n, _ in decks}) != len(decks):
        raise ValueError('Deck names must be unique and nonempty')
    for _, path in decks:
        if not path.is_file(): raise ValueError(f'Missing deck: {path}')
    args.output.mkdir(parents=True, exist_ok=False)
    baseline = args.baseline.resolve()
    manifest = json.loads((baseline / 'manifest.json').read_text())
    for name, value in manifest['files'].items():
        if digest(baseline / name) != value: raise ValueError(f'Changed baseline: {name}')
    metadata = {'config': config, 'baseline': manifest, 'candidateHashes': {f: digest(ROOT / 'GrandArchiveSim' / f) for f in FILES}, 'deckHashes': {n: digest(p) for n, p in decks}, 'engineHashes': {str(p.relative_to(ROOT)): digest(p) for p in list((ROOT / 'GrandArchiveSim/Custom').glob('*.php')) + [ROOT / 'GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php', ROOT / 'GrandArchiveSim/CreateGame.php']}}
    (args.output / 'metadata.json').write_text(json.dumps(metadata, indent=2))
    games = []
    schedule = make_schedule(decks, config['seeds'])
    if args.smoke: schedule = schedule[:2]
    for number, (a, b, seed, seat) in enumerate(schedule, 1):
        for name, expected in metadata['candidateHashes'].items():
            if digest(ROOT / 'GrandArchiveSim' / name) != expected:
                raise RuntimeError('Candidate policy changed during evaluation: ' + name)
        for name, path in decks:
            if digest(path) != metadata['deckHashes'][name]:
                raise RuntimeError('Deck changed during evaluation: ' + name)
        p1, p2 = (a, b) if seat == 1 else (b, a)
        report = args.output.resolve() / f'{number:04d}.json'
        cmd = [args.php, '-d', 'apc.enable_cli=1', '-d', 'xdebug.mode=off', str(ROOT / 'DevTools/GABotSelfPlayTest.php'), f'--deck={p1[1]}', f'--deck2={p2[1]}', f'--seed={seed}', f'--candidate-seat={seat}', f'--baseline={baseline}', f'--report-out={report}', f'--max-steps={args.max_steps}']
        with (args.output / f'{number:04d}.log').open('w') as log:
            try:
                proc = subprocess.run(cmd, cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, timeout=args.timeout)
                code = proc.returncode
            except subprocess.TimeoutExpired:
                code = 'timeout'
        result = json.loads(report.read_text()) if report.exists() else {'completed': False}
        result['rejectedActionAttempts'] = (args.output / f'{number:04d}.log').read_text(errors='replace').count('action produced no gamestate change')
        result.update(candidateDeck=a[0], baselineDeck=b[0], candidateSeat=seat, seed=seed, exitCode=code)
        games.append(result)
        valid = [g for g in games if g['completed'] and g['exitCode'] == 0]
        summary = {'scheduled': len(schedule), 'played': len(games), 'valid': len(valid), 'failed': len(games)-len(valid), 'candidateWins': sum(g['winner'] == g['candidateSeat'] for g in valid), 'games': games}
        (args.output / 'summary.json').write_text(json.dumps(summary, indent=2))
        write_report(args.output, summary)
        print(f'{number}/{len(schedule)} {a[0]} vs {b[0]} seed={seed} candidate=P{seat}: ' + (f"winner=P{result.get('winner')}" if result['completed'] else 'FAILED'), flush=True)
    return 1 if summary['failed'] else 0

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='command', required=True)
    p = sub.add_parser('freeze'); p.add_argument('destination', type=pathlib.Path)
    p = sub.add_parser('run')
    p.add_argument('--config', type=pathlib.Path, required=True); p.add_argument('--baseline', type=pathlib.Path, required=True)
    p.add_argument('--output', type=pathlib.Path, required=True); p.add_argument('--php', default='php')
    p.add_argument('--smoke', action='store_true'); p.add_argument('--max-steps', type=int, default=2000); p.add_argument('--timeout', type=int, default=180)
    args = parser.parse_args()
    if args.command == 'freeze': freeze(args.destination)
    else: sys.exit(run(args))
