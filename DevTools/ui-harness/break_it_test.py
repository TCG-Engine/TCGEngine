#!/usr/bin/env python3
"""Tests for break-it.py's restore step: it must never overwrite edits made to the file while the
harness ran (final review 2026-10-08). Run: python3 DevTools/ui-harness/break_it_test.py"""
import importlib.util, os, tempfile, sys

spec = importlib.util.spec_from_file_location('break_it', os.path.join(os.path.dirname(__file__), 'break-it.py'))
bi = importlib.util.module_from_spec(spec); spec.loader.exec_module(bi)

fails = 0
def check(cond, msg):
    global fails
    print(('ok   ' if cond else 'FAIL ') + msg); fails += 0 if cond else 1

def scenario(during):
    d = tempfile.mkdtemp(); path = os.path.join(d, 'f.php')
    original = "line one\nKEEP = 1;\nline three\n"
    open(path, 'w').write(original)
    m = {'name': 't', 'file': path, 'old': 'KEEP = 1;', 'new': 'KEEP = 2;'}
    mutated = original.replace(m['old'], m['new'])
    open(path, 'w').write(mutated)
    during(path)                                   # someone edits the file while the harness runs
    restored_ok = bi.restore(m, original, mutated)
    return restored_ok, open(path).read()

# 1. Untouched during the run: plain restore.
ok1, text = scenario(lambda p: None)
check(ok1 and text == "line one\nKEEP = 1;\nline three\n", 'untouched file is restored exactly')
# 2. Someone appended a line during the run: keep their line, reverse only the mutation.
ok2, text = scenario(lambda p: open(p, 'a').write('THEIR EDIT\n'))
check(ok2 and text == "line one\nKEEP = 1;\nline three\nTHEIR EDIT\n", 'a concurrent edit is kept and only the mutation reversed')
# 3. Someone rewrote the mutated line itself: can't reverse safely; leave the file alone and report.
ok3, text = scenario(lambda p: open(p, 'w').write('totally different\n'))
check((not ok3) and text == 'totally different\n', 'an unreversible edit is left untouched and reported')

print('PASS' if not fails else f'{fails} FAILED'); sys.exit(1 if fails else 0)
