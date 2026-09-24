"""Strength report (strength_report.py) — the PER-STYLE split, against a synthetic results.tsv whose
answers are known by construction.

⚠ WHY THIS EXISTS. The per-style section printed NOTHING for the real fixture set and had been doing so
silently. It derived the style from `deckname.split('_')[0]` — which yields "lando", "ahsoka", "piett" —
and then only looked for the three names ('aggro', 'normal', 'control'), so every row hit the `continue`
and the whole section vanished. The overnight kill-weight screen of 2026-09-24 was read as "flat, nothing
found" from the aggregate line alone; the per-style split (computed by hand afterwards) showed midrange
+2.2pp at p<=0.004. That section is the owner's "raise the weak, never lower the strong" check, so a
silent skip is the worst possible failure mode.

Runs the script as a SUBPROCESS: that is the entry point strength_test.sh actually calls, and a unit test
of an inner helper would not have caught the bug above.
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 python3 SWUSim/DevTools/rl/strength_report_test.py
"""
import json, os, shutil, subprocess, sys, tempfile

HERE = os.path.dirname(os.path.abspath(__file__))
SCRIPT = os.path.join(HERE, 'strength_report.py')

fails = 0
def check(ok, msg):
    global fails
    print(('PASS: ' if ok else 'FAIL: ') + msg)
    if not ok:
        fails += 1

def row(a, b, seed, side, winner):
    m = 'SWUBOT_METRICS ' + json.dumps({'winner': winner})
    return f"{a}\t{b}\t{seed}\t{side}\t{m}\t123\n"

root = tempfile.mkdtemp(prefix='strengthreport_')
try:
    decks = os.path.join(root, 'decks')
    os.makedirs(decks)
    # Deck names deliberately share no prefix with any style name — the shape that broke the old code.
    for name, style in (('lando_blue', 'midrange'), ('ahsoka_red', 'softaggro')):
        with open(os.path.join(decks, name + '.txt'), 'w') as f:
            f.write(f"# Style: {style}\n3 ASH_009\n")   # the real fixture header shape

    # Four mirrored games. Worked out by hand:
    #   midrange  — NEW pilots it twice and wins both (100%); OLD pilots it twice and wins once (50%)
    #   softaggro — NEW pilots it twice and wins once (50%);  OLD pilots it twice and wins none (0%)
    #   all       — NEW wins 3 of 4 (75%)
    res = os.path.join(root, 'results.tsv')
    with open(res, 'w') as f:
        f.write(row('lando_blue', 'ahsoka_red', 's1', '1', 1))
        f.write(row('lando_blue', 'ahsoka_red', 's1', '2', 2))
        f.write(row('ahsoka_red', 'lando_blue', 's2', '1', 2))
        f.write(row('ahsoka_red', 'lando_blue', 's2', '2', 2))

    out = subprocess.run([sys.executable, SCRIPT, res, decks],
                         capture_output=True, text=True).stdout
    print('--- report ---\n' + out + '--------------')

    check('new wins 3/4 = 75.0%' in out, 'the aggregate line still reports 3/4 = 75.0%')
    # THE REGRESSION GUARD: the section must appear at all, named by the REAL styles.
    check('midrange' in out, 'a per-style line is printed for midrange')
    check('softaggro' in out, 'a per-style line is printed for softaggro')
    mid = next((l for l in out.splitlines() if l.startswith('midrange')), '')
    agg = next((l for l in out.splitlines() if l.startswith('softaggro')), '')
    check('100.0% (2/2)' in mid and '50.0% (1/2)' in mid, f'midrange new 100% (2/2) vs old 50% (1/2); got: {mid}')
    check('50.0% (1/2)' in agg and '0.0% (0/2)' in agg, f'softaggro new 50% (1/2) vs old 0% (0/2); got: {agg}')
    check(out.count('games without a winner') == 1, 'the no-winner footer is still printed once')

    # A deck absent from the fixture dir must be SAID, never silently dropped — that is the whole lesson.
    with open(res, 'a') as f:
        f.write(row('ghost_deck', 'lando_blue', 's3', '1', 1))
    out2 = subprocess.run([sys.executable, SCRIPT, res, decks],
                          capture_output=True, text=True).stdout
    check('ghost_deck' in out2, 'a deck with no fixture is named in the output, not skipped in silence')
finally:
    shutil.rmtree(root, ignore_errors=True)

print('\nALL PASS' if fails == 0 else f'\n{fails} FAILED')
sys.exit(0 if fails == 0 else 1)
