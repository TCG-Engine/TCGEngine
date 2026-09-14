#!/usr/bin/env python3
"""RL Phase 3 MVP — the training loop (plan: docs/superpowers/plans/2026-09-14-swusim-rl-phase3-trainer-mvp.md).

Runs INSIDE the SWUSim container:
    docker exec -d -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \\
        python3 SWUSim/DevTools/rl/rl_train.py --out /tmp/rl_run1 --hours 8 --workers 8 --batch 240

Stage 1 of the spec's schedule (Section 4): each style trains against FIXED heuristic opponents of all three styles.
Every game: a learner deck (its fixture style, the '@rl' chooser in train mode) vs any other fixture deck (the plain
heuristic of its own style); the learner's seat alternates. Games run through the self-play harness with the pairing's
round cap. After each batch the episodes are folded into the table (rl_merge.py), the checkpoint is written atomically,
and one JSON metrics line goes to <out>/train.log (spec's health metrics: new states per decision, lengths per pairing).
Resumes from <out>/checkpoint.json when it exists. A snapshot is kept every --snapshot-every batches.
"""
import argparse, json, os, random, re, shutil, statistics, subprocess, sys, time
from concurrent.futures import ThreadPoolExecutor

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from rl_merge import merge_batch

ROOT = '/var/www/html/TCGEngine'
FIXTURES = 'SWUSim/Tests/BotFixtures/meta-2026-09'
# Spec Section 4: 1.5x the top of each pairing's expected range.
CAPS = {('aggro', 'aggro'): 12, ('aggro', 'normal'): 18, ('aggro', 'control'): 24,
        ('normal', 'normal'): 23, ('control', 'normal'): 27, ('control', 'control'): 30}
STYLES = ['aggro', 'normal', 'control']


def pairing(a, b):
    return tuple(sorted((a, b)))


def deck_styles():
    out = {}
    for f in sorted(os.listdir(os.path.join(ROOT, FIXTURES))):
        if not f.endswith('.txt'):
            continue
        with open(os.path.join(ROOT, FIXTURES, f)) as fh:
            for line in fh:
                m = re.match(r'^# Style:\s*(\w+)', line)
                if m:
                    out[f[:-4]] = m.group(1).lower()
                    break
    return out


def make_jobs(batch, n, decks, rng):
    jobs = []
    for i in range(n):
        ls = STYLES[i % 3]
        ld = rng.choice([d for d, s in decks.items() if s == ls])
        od = rng.choice([d for d in decks if d != ld])
        seat = 1 if (i // 3) % 2 == 0 else 2
        jobs.append({'id': f'b{batch}-{i}', 'seed': f'b{batch}-{i}', 'learnerSeat': seat, 'learnerStyle': ls,
                     'learnerDeck': ld, 'opponentDeck': od, 'opponentStyle': decks[od]})
    return jobs


def run_job(job, policy, epdir, eps, timeout):
    seat = job['learnerSeat']
    decks = (job['learnerDeck'], job['opponentDeck']) if seat == 1 else (job['opponentDeck'], job['learnerDeck'])
    styles = (job['learnerStyle'], job['opponentStyle']) if seat == 1 else (job['opponentStyle'], job['learnerStyle'])
    ch = [f'heuristic-{styles[0]}' + ('@rl' if seat == 1 else ''), f'heuristic-{styles[1]}' + ('@rl' if seat == 2 else '')]
    ep = os.path.join(epdir, job['id'] + '.jsonl')
    env = dict(os.environ, SWU_RL_POLICY=policy, SWU_RL_MODE='train', SWU_RL_EPSILON=str(eps),
               SWU_RL_EPISODE=ep, SWU_RL_SEED=job['seed'])
    cmd = ['php', '-d', 'apc.enable_cli=1', '-d', 'xdebug.mode=off', '-d', 'memory_limit=1G',
           'DevTools/SWUSimBotSelfPlayTest.php', '--games=1', '--seed=' + job['seed'], '--first-player=1', '--verbose',
           '--chooser=' + ch[0], '--chooser2=' + ch[1], f'--deck={FIXTURES}/{decks[0]}.txt', f'--deck2={FIXTURES}/{decks[1]}.txt',
           f'--max-rounds={CAPS[pairing(*styles)]}']
    metrics, game = None, None
    try:
        out = subprocess.run(cmd, cwd=ROOT, env=env, capture_output=True, text=True, timeout=timeout).stdout
        for line in out.splitlines():
            if line.startswith('SWUBOT_METRICS '):
                metrics = json.loads(line[len('SWUBOT_METRICS '):])
            elif 'game created:' in line:
                game = line.split()[-1]
    except Exception:
        metrics = None
    if metrics and game and game.isdigit():
        shutil.rmtree(os.path.join(ROOT, 'SWUSim/Games', game), ignore_errors=True)   # keep a folder only on failure
    records = []
    if os.path.exists(ep):
        with open(ep) as fh:
            records = [json.loads(l) for l in fh if l.strip()]
        os.remove(ep)
    return dict(job, metrics=metrics, records=records, pairing='/'.join(pairing(*styles)))


def write_json_atomic(path, payload):
    tmp = path + '.tmp'
    with open(tmp, 'w') as fh:
        json.dump(payload, fh, separators=(',', ':'))
    os.replace(tmp, path)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--out', required=True)
    ap.add_argument('--hours', type=float, default=8.0)
    ap.add_argument('--batches', type=int, default=0, help='stop after N batches (0 = run until --hours)')
    ap.add_argument('--max-games', type=int, default=0, help='stop once the checkpoint holds N games (0 = no limit)')
    ap.add_argument('--collect-only', action='store_true',
                    help='pure data collection: no policy is loaded by the games (the heuristic + epsilon only) — faster')
    ap.add_argument('--workers', type=int, default=8)
    ap.add_argument('--batch', type=int, default=240)
    ap.add_argument('--epsilon', type=float, default=0.1)
    ap.add_argument('--timeout', type=int, default=150)
    ap.add_argument('--snapshot-every', type=int, default=20)
    a = ap.parse_args()
    os.makedirs(a.out, exist_ok=True)
    epdir = os.path.join(a.out, 'ep'); os.makedirs(epdir, exist_ok=True)
    ckpt = os.path.join(a.out, 'checkpoint.json')
    state = {'version': 'swu-v1', 'batches': 0, 'games': 0, 'table': {}}
    if os.path.exists(ckpt):
        with open(ckpt) as fh:
            state = json.load(fh)
    decks = deck_styles()
    deadline = time.time() + a.hours * 3600
    log = open(os.path.join(a.out, 'train.log'), 'a')
    print(f'[rl] {len(decks)} decks, resume at batch {state["batches"]}, {a.workers} workers, {a.batch} games/batch', flush=True)
    done = 0
    while time.time() < deadline and (a.batches == 0 or done < a.batches) and (a.max_games == 0 or state['games'] < a.max_games):
        b = state['batches'] + 1
        t0 = time.time()
        # One frozen file per style: a game's PHP process loads only its learner's table (the checkpoint grows).
        frozen = {}
        for s in STYLES:
            if a.collect_only:
                frozen[s] = ''   # SWURlPolicy() returns an empty table without reading anything
                continue
            frozen[s] = os.path.join(a.out, f'policy_frozen_{s}.json')
            write_json_atomic(frozen[s], {'version': 'swu-v1', 'table': {s: state['table'].get(s, {})}})
        jobs = make_jobs(b, a.batch, decks, random.Random(f'rl-{b}'))
        with ThreadPoolExecutor(max_workers=a.workers) as pool:
            results = list(pool.map(lambda j: run_job(j, frozen[j['learnerStyle']], epdir, a.epsilon, a.timeout), jobs))
        stats = merge_batch(state['table'], results)
        state['batches'] = b
        state['games'] += stats['games']
        write_json_atomic(ckpt, state)
        if b % a.snapshot_every == 0:
            shutil.copyfile(ckpt, os.path.join(a.out, f'checkpoint_b{b:04d}.json'))
        line = {'batch': b, 'time': time.strftime('%H:%M:%S'), 'secs': round(time.time() - t0, 1),
                'games': stats['games'], 'failed': stats['failed'],
                'capped_pct': round(100.0 * stats['capped'] / max(1, stats['games']), 1),
                'learner_win_pct': {s: round(100.0 * w / max(1, n), 1) for s, (w, n) in stats['learner_wins'].items()},
                'median_rounds': {p: statistics.median(r) for p, r in sorted(stats['rounds'].items())},
                'decisions_per_game': round(stats['decisions'] / max(1, stats['games']), 1),
                'new_states_per_decision': round(stats['new_states'] / max(1, stats['decisions']), 3),
                'states': {s: len(t) for s, t in state['table'].items()},
                'total_games': state['games']}
        log.write(json.dumps(line) + '\n'); log.flush()
        print(json.dumps(line), flush=True)
        done += 1
    print('[rl] stopped', flush=True)


if __name__ == '__main__':
    main()
