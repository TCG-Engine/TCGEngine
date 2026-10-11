#!/usr/bin/env python3
"""Mutation check for a ui-harness: apply each mutation, run the harness, expect it to FAIL, restore.

Restores IN PLACE and touches the file: a rename-restore brings back an older mtime and PHP's opcache
keeps serving the mutant (found 2026-10-08). ⚠ A run takes minutes, and the owner or another agent may
edit the same file meanwhile: restore() never overwrites their edit. It reverses only the mutation and
refuses (reporting it) when that can't be done safely. Ends with a CLEAN CONTROL run.

Usage:  python3 DevTools/ui-harness/break-it.py <harness.mjs> <ONLY sections> < mutations.json
        mutations.json = [{"name": "...", "file": "repo/path", "old": "exact text", "new": "replacement"}]
Tests:  python3 DevTools/ui-harness/break_it_test.py
"""
import json, os, subprocess, sys


def restore(m, original, mutated):
    """Put the file back. True when restored; False (file left untouched) when it can't be done safely."""
    current = open(m['file']).read()
    if current == mutated:
        text = original                                        # nobody touched it: exact restore
    elif current.count(m['new']) == 1:
        text = current.replace(m['new'], m['old'])             # concurrent edit elsewhere: keep it
    else:
        print(f"⚠ NOT RESTORED {m['file']}: it changed during the run and the mutation can't be located; "
              f"undo by hand: replace {m['new']!r} with {m['old']!r}")
        return False
    open(m['file'], 'w').write(text)
    os.utime(m['file'])
    return True


def main(argv):
    harness, only = argv[1], argv[2]
    env = {**os.environ, 'ONLY': only}

    def run():
        out = subprocess.run(['node', harness], env=env, capture_output=True, text=True).stdout.strip().splitlines()
        return (out[-1] if out else '(no output)'), [l for l in out if l.startswith('FAIL')]

    missed = 0
    for m in json.load(sys.stdin):
        src = open(m['file']).read()
        n = src.count(m['old'])
        if n != 1:
            print(f"SKIP   {m['name']}: 'old' text found {n} times in {m['file']}")
            missed += 1
            continue
        mutated = src.replace(m['old'], m['new'])
        restored = False
        try:
            open(m['file'], 'w').write(mutated)
            os.utime(m['file'])
            last, fails = run()
            caught = 'FAILED' in last
            print(f"{'caught' if caught else 'MISSED'} {m['name']}: {last}" + (f"   e.g. {fails[0]}" if fails else ''))
            missed += 0 if caught else 1
        finally:
            restored = restore(m, src, mutated)   # always, even if the harness run raised
        if not restored:
            return 2

    last, _ = run()
    print('CLEAN CONTROL:', last)
    missed += 0 if last.startswith('PASS') else 1
    return 1 if missed else 0


if __name__ == '__main__':
    sys.exit(main(sys.argv))
