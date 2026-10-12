"""Shared loaders for the meta-baseline tools (skill `swusim-ash-meta-bot-run`).

A META DIR (e.g. SWUSim/Tests/BotFixtures/meta-baselines/ash) holds one tournament set, frozen:
  fixtures.tar.gz   every player's real list as a fixture (main deck + Sideboard section), named <melee DecklistId>.txt
  matches.tsv.gz    one row per real match: srcA srcB gamesWonA gamesLostA draws archKeyA archKeyB styleA styleB
  labels.json       archetype key -> human label ("Director Krennic (LAW) · Blue 30HP")
  events.txt        the melee tournament ids (tournamentLink) the set was built from
  baseline/         the frozen bot run everything is compared against: bot_games.tsv.gz (deck1 deck2 winner rounds),
                    matrix.json, analysis.txt, meta.json
A RUN DIR is a sweep_fixtures.sh output dir (results.tsv) made by run.sh.
"""
import gzip, json, math, os, statistics, collections


def _open(path):
    return gzip.open(path, 'rt') if path.endswith('.gz') else open(path)


def on_ibh_base(key):
    """True for an archetype key (LEADER|BASE) on an Intro Battle: Hoth base (IBH_002 Echo Caverns, IBH_054 Forward Command Post).
    Those bases are double-sided: the real lists played the 30 HP side, the sims play the 20 HP intro side (owner 2026-10-12:
    keep 20 in the sims, ignore these matches in the analyses)."""
    return key.split('|', 1)[-1].startswith('IBH_')


def load_matches(meta, include_ibh=False):
    """The real matches; by default without any match where either deck is on an Intro Battle: Hoth base (see on_ibh_base)."""
    out = []
    for l in _open(os.path.join(meta, 'matches.tsv.gz')):
        p = l.rstrip('\n').split('\t')
        if len(p) < 9: continue
        a, b, w, lo, dr, ka, kb, sa, sb = p[:9]
        if not include_ibh and (on_ibh_base(ka) or on_ibh_base(kb)): continue
        out.append(dict(a=a, b=b, w=int(w), l=int(lo), d=int(dr), ka=ka, kb=kb, sa=sa, sb=sb))
    return out


def load_labels(meta):
    return json.load(open(os.path.join(meta, 'labels.json')))


def load_run(run_dir):
    """(deck1, deck2) -> (winner seat 1/2 or 0, rounds) from a sweep's results.tsv. Failed games are skipped."""
    bot, fails = {}, 0
    for l in open(os.path.join(run_dir, 'results.tsv')):
        p = l.rstrip('\n').split('\t')
        if len(p) < 5: continue
        d1, d2, s, m, r = p[:5]
        if not m.startswith('SWUBOT_METRICS ') or '"fail":0' not in r: fails += 1; continue
        met = json.loads(m.split(' ', 1)[1])
        bot[(d1, d2)] = (int(met.get('winner', 0) or 0), int(met.get('rounds', 0) or 0))
    return bot, fails


def load_baseline(meta):
    path = os.path.join(meta, 'baseline', 'bot_games.tsv.gz')
    if not os.path.exists(path): return None
    bot = {}
    for l in _open(path):
        d1, d2, w, r = l.rstrip('\n').split('\t')
        bot[(d1, d2)] = (int(w), int(r))
    return bot


def replay(matches, bot):
    """Attach the bot's two games to each real match: botA / botB = games each side won (0-2)."""
    done = []
    for m in matches:
        g1 = bot.get((m['a'], m['b'])); g2 = bot.get((m['b'], m['a']))
        if g1 is None or g2 is None: continue
        m = dict(m); m['botA'] = (g1[0] == 1) + (g2[0] == 2); m['botB'] = (g1[0] == 2) + (g2[0] == 1)
        done.append(m)
    return done


def archetypes(done, min_matches=30):
    """[(key, realWR, botWR, matches, style)] for archetypes with min_matches+ replayed matches."""
    arch = collections.defaultdict(lambda: {'rw': 0, 'rl': 0, 'bw': 0, 'bl': 0, 'n': 0, 'style': collections.Counter()})
    for m in done:
        for k, s, rw, rl, bw, bl in ((m['ka'], m['sa'], m['w'], m['l'], m['botA'], m['botB']),
                                     (m['kb'], m['sb'], m['l'], m['w'], m['botB'], m['botA'])):
            x = arch[k]; x['rw'] += rw; x['rl'] += rl; x['bw'] += bw; x['bl'] += bl; x['n'] += 1; x['style'][s] += 1
    return [(k, x['rw'] / (x['rw'] + x['rl']), x['bw'] / (x['bw'] + x['bl']), x['n'], x['style'].most_common(1)[0][0])
            for k, x in arch.items() if x['n'] >= min_matches and x['rw'] + x['rl'] and x['bw'] + x['bl']]


def cells(done):
    """Ordered (deck, opp) archetype pairs -> [realWon, realLost, botWon, botLost, matches]."""
    c = collections.defaultdict(lambda: [0, 0, 0, 0, 0])
    for m in done:
        if m['ka'] in ('', 'None') or m['kb'] in ('', 'None') or m['ka'] == m['kb']: continue
        for d, o, rw, rl, bw, bl in ((m['ka'], m['kb'], m['w'], m['l'], m['botA'], m['botB']),
                                     (m['kb'], m['ka'], m['l'], m['w'], m['botB'], m['botA'])):
            x = c[(d, o)]; x[0] += rw; x[1] += rl; x[2] += bw; x[3] += bl; x[4] += 1
    return c


def styles(done):
    s = collections.defaultdict(collections.Counter)
    for m in done: s[m['ka']][m['sa']] += 1; s[m['kb']][m['sb']] += 1
    return {k: v.most_common(1)[0][0] for k, v in s.items()}


def pearson(x, y):
    if len(x) < 3: return float('nan')
    mx, my = statistics.mean(x), statistics.mean(y)
    sx = math.sqrt(sum((a - mx) ** 2 for a in x)); sy = math.sqrt(sum((b - my) ** 2 for b in y))
    return sum((a - mx) * (b - my) for a, b in zip(x, y)) / (sx * sy) if sx and sy else float('nan')


def _rank(v):
    o = sorted(range(len(v)), key=lambda i: v[i]); r = [0.0] * len(v); i = 0
    while i < len(o):
        j = i
        while j + 1 < len(o) and v[o[j + 1]] == v[o[i]]: j += 1
        for k in range(i, j + 1): r[o[k]] = (i + j) / 2
        i = j + 1
    return r


def spearman(x, y): return pearson(_rank(x), _rank(y))


def sign_p(a, b):
    n = a + b
    if n == 0: return 1.0
    return min(1.0, 2 * sum(math.comb(n, i) for i in range(0, min(a, b) + 1)) / 2 ** n)
