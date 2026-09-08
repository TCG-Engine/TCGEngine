#!/usr/bin/env python3
"""
Cost-curve analysis — step 2: fit the SWU unit stat curve.

    php dump-cards.php > cards.tsv      # inside the swusim container
    python3 fit.py cards.tsv

Model
-----
For every unit whose printed box contains NOTHING but keywords, regress the
point total (power + HP) on cost, arena, aspect pips, uniqueness, the Force
trait, and each keyword.  Keyword coefficients come out NEGATIVE: a keyword is
paid for in stats.  See SWUSim/docs/cost-curve.md for the write-up.
"""
import csv, sys, re, random, collections
import numpy as np

BASIC = {"Command", "Aggression", "Cunning", "Vigilance"}
ALIGN = {"Heroism", "Villainy"}
# Keywords priced directly against the stat line.  Bounty belongs here and NOT in DIRTY: it is a
# DRAWBACK (the opponent collects the reward), so its coefficient comes out POSITIVE — the card is
# handed extra stats to carry it.
STAT_KW = ["Sentinel", "Saboteur", "Overwhelm", "Ambush", "Shielded", "Hidden", "Grit", "Bounty"]
NUM_KW = ["Raid", "Restore"]            # priced per point of N
# Keywords that carry non-stat value (an alternate cost, a reward, a discount).
# A unit with one of these is excluded from the fit — it would pollute the curve.
# Excluded from the pool entirely.  Exploit belongs here even though it is measurable: it changes
# the card's EFFECTIVE COST, so an additive stat term on a printed-cost curve mis-specifies it.
# Adding it flattened cost^2 from 0.063 (t=6.6) to 0.013 (t=1.7) and pushed residual sd 0.46 -> 0.52.
# It is analysed separately in the docs, against this curve rather than inside it.
DIRTY = ["Piloting", "Support", "Exploit"]
# Flexibility keywords: priced, not excluded (see terms()).
FLEX_KW = ["Plot", "Smuggle"]
ALL_KW = STAT_KW + NUM_KW + FLEX_KW + DIRTY
# Release order, so dedupe keeps the earliest printing.
SET_ORDER = ["SOR", "SHD", "TWI", "JTL", "LOF", "IBH", "TS26", "SEC", "LAW", "ASH", "HMW", "IC27"]
# Non-Premier products, excluded from the curve by default (--all keeps them):
#   IBH  intro/beginner decks, printed a full point under curve
#   TS26 Twin Suns starter — singleton format, so bodies run slightly hot
NON_PREMIER = {"IBH", "TS26"}
# Power is worth more than HP, so the response is weighted.  The run re-derives ALPHA by
# search + bootstrap and asserts this constant still matches.
ALPHA = 1.32


def parse_keywords(text):
    """Split printed text into {keyword: N} and the residual (real ability) text."""
    kws, residual = {}, []
    for seg in (s.strip() for s in text.split(" ~~ ")):
        if not seg:
            continue
        m = re.match(r"^([A-Z][a-z]+)(?:\s+(\d+))?\s*(?:\[[^\]]*\])?\s*\(", seg)
        if m and m.group(1) in ALL_KW:
            kws[m.group(1)] = int(m.group(2)) if m.group(2) else 1
            continue
        # Reminder text is not printed on every card.  A clause that is EXACTLY a keyword
        # (plus its number) is that keyword and nothing else -- 157 clauses in the corpus.
        m = re.match(r"^([A-Z][a-z]+)(?:\s+(\d+))?$", seg)
        if m and m.group(1) in ALL_KW:
            kws[m.group(1)] = int(m.group(2)) if m.group(2) else 1
            continue
        # "Bounty - <reward>. (reminder)" and the bracketed-cost keywords
        m = re.match(r"^(%s)\b" % "|".join(ALL_KW), seg)
        if m and "(" in seg and seg.index("(") < 40:
            kws.setdefault(m.group(1), 1)
            continue
        residual.append(seg)
    return kws, " ~~ ".join(residual)


def load(path):
    rows = list(csv.DictReader(open(path), delimiter="\t"))
    for r in rows:
        for f in ("cost", "power", "hp"):
            r[f] = int(r[f]) if r[f] else None
        r["asp"] = [a for a in r["aspects"].split(",") if a]
        r["nb"] = sum(1 for a in r["asp"] if a in BASIC)
        r["na"] = sum(1 for a in r["asp"] if a in ALIGN)
        r["traitlist"] = [t for t in r["traits"].split(",") if t]
        r["kw"], r["resid"] = parse_keywords(r["text"])
        r["stats"] = None if r["power"] is None or r["hp"] is None else r["power"] + r["hp"]
    return rows


def clean_pool(rows, premier_only=True):
    """Units with no ability text and no value-bearing keyword, one row per card."""
    pool = [r for r in rows
            if r["type"] == "Unit" and "_T" not in r["id"] and r["cost"] is not None
            and not r["resid"].strip() and not any(k in r["kw"] for k in DIRTY)
            and not (premier_only and r["set"] in NON_PREMIER)]
    seen = {}
    for r in sorted(pool, key=lambda r: SET_ORDER.index(r["set"]) if r["set"] in SET_ORDER else 99):
        seen.setdefault((r["title"], r["subtitle"], r["cost"], r["power"], r["hp"], r["arena"]), r)
    return pool, list(seen.values())


def terms():
    t = collections.OrderedDict()
    t["intercept"]  = lambda r: 1
    t["cost"]       = lambda r: r["cost"]
    # The marginal points-per-cost RISES with cost: ~1.5 at the low end, ~2.0 by cost 6-7.
    # Dropping this term costs 0.04 of residual sd and leaves a U-shaped residual by cost.
    t["cost^2"]     = lambda r: r["cost"] ** 2
    t["Space"]      = lambda r: 1 if r["arena"] == "Space" else 0
    t["cost*Space"] = lambda r: r["cost"] if r["arena"] == "Space" else 0
    t["pip_basic"]  = lambda r: r["nb"]
    t["pip_align"]  = lambda r: r["na"]
    t["unique"]     = lambda r: 1 if str(r["unique"]) == "1" else 0
    # The Force trait measured -0.05 (t = -0.4) and is dropped.  No trait shows a premium.
    for k in STAT_KW:
        t[k] = (lambda k: (lambda r: 1 if k in r["kw"] else 0))(k)
    for k in NUM_KW:
        t[k + "_N"] = (lambda k: (lambda r: r["kw"].get(k, 0)))(k)
    # Flexibility keywords: both let you play the card out of your resources and replace the
    # resource off the top of your deck.  Plot always costs the printed cost but only fires on a
    # leader deploy; Smuggle works any time but usually taxes the cost, so its value has to be
    # read against that tax.
    t["Plot"] = lambda r: 1 if "Plot" in r["kw"] else 0
    t["Smuggle"] = lambda r: 1 if "Smuggle" in r["kw"] else 0
    t["Smuggle_delta"] = lambda r: smuggle_cost_delta(r)
    return t


def exploit_n(r):
    """Printed Exploit N, 0 for a card without it."""
    if "Exploit" not in r["kw"]:
        return 0
    m = re.search(r"Exploit\s+(\d+)", r["text"])
    return int(m.group(1)) if m else 1


def smuggle_cost_delta(r):
    """Smuggle cost minus printed cost, 0 for a card without Smuggle."""
    if "Smuggle" not in r["kw"]:
        return 0
    m = re.search(r"Smuggle\s*\[([^\]]*)\]", r["text"])
    if not m:
        return 0
    c = re.search(r"(\d+)\s*resources?", m.group(1))
    return (int(c.group(1)) - r["cost"]) if c else 0


def ols(pool, t, response=None):
    X = np.array([[f(r) for f in t.values()] for r in pool], float)
    y = np.array([(response(r) if response else r["stats"]) for r in pool], float)
    beta, _, _, _ = np.linalg.lstsq(X, y, rcond=None)
    res = y - X @ beta
    n, k = X.shape
    se = np.sqrt(np.diag((res @ res / (n - k)) * np.linalg.pinv(X.T @ X)))
    r2 = 1 - (res @ res) / ((y - y.mean()) @ (y - y.mean()))
    return beta, se, res, r2


def main(path, premier_only=True):
    rows = load(path)
    pool, dd = clean_pool(rows, premier_only)
    t = terms()
    print("scope:", "PREMIER (excluding %s)" % ", ".join(sorted(NON_PREMIER))
          if premier_only else "ALL SETS")
    weight = lambda r: (ALPHA * r["power"] + r["hp"]) / ((ALPHA + 1) / 2)
    beta, se, res, r2 = ols(dd, t, weight)
    C = dict(zip(t, beta))
    _, _, res_raw, r2_raw = ols(dd, t)

    print(f"pool {len(pool)} rows -> {len(dd)} after dropping {len(pool)-len(dd)} reprints")
    print(f"response: ({ALPHA}*power + HP) normalised   "
          f"[unweighted power+HP for comparison: R^2={r2_raw:.4f} sd={res_raw.std():.3f}]")
    print(f"\n=== MODEL   n={len(dd)}  R^2={r2:.4f}  residual sd={res.std():.3f} points ===")
    print(f"{'term':<13}{'coef':>8}{'se':>7}{'t':>7}")
    for nm, b, s in zip(t, beta, se):
        print(f"{nm:<13}{b:>8.3f}{s:>7.3f}{b/s:>7.2f}{'  *' if abs(b/s) > 2 else ''}")

    print("\n=== BASELINE POINT TOTAL (power+HP): keyword-free, non-unique ===")
    mk = lambda c, a, n: {"cost": c, "arena": a, "nb": n, "na": 0, "unique": "0",
                          "traitlist": [], "kw": {}}
    pred = lambda r: float(np.array([f(r) for f in t.values()], float) @ beta)
    hdr = "".join(f"{'G '+str(n)+'pip':>8}" for n in range(4)) + " |" + \
          "".join(f"{'S '+str(n)+'pip':>8}" for n in range(4))
    print(f"{'cost':>4} |{hdr}")
    for c in range(0, 10):
        g = "".join(f"{pred(mk(c,'Ground',n)):>8.1f}" for n in range(4))
        s = "".join(f"{pred(mk(c,'Space',n)):>8.1f}" for n in range(4))
        print(f"{c:>4} |{g} |{s}")

    print("\n=== OBSERVED, normalised to a 0-pip keyword-free unit ===")
    def norm(r):
        v = weight(r)
        for k in STAT_KW:
            v -= C[k] * (1 if k in r["kw"] else 0)
        for k in NUM_KW:
            v -= C[k + "_N"] * r["kw"].get(k, 0)
        v -= C["pip_basic"] * r["nb"] + C["pip_align"] * r["na"]
        v -= C["unique"] * (1 if str(r["unique"]) == "1" else 0)
        return v
    by = collections.defaultdict(list)
    for r in dd:
        by[(r["arena"], r["cost"])].append(norm(r))
    print(f"{'cost':>4} |{'G n':>5}{'G obs':>8}{'G sd':>6}{'G fit':>7} |{'S n':>5}{'S obs':>8}{'S sd':>6}{'S fit':>7}")
    for c in range(0, 10):
        out = f"{c:>4} |"
        for a in ("Ground", "Space"):
            v = by[(a, c)]
            fit = (C["intercept"] + C["cost"] * c + C["cost^2"] * c * c
                   + (C["Space"] + C["cost*Space"] * c if a == "Space" else 0))
            out += (f"{len(v):>5}{np.mean(v):>8.2f}{np.std(v):>6.2f}{fit:>7.2f} |"
                    if v else f"{0:>5}{'-':>8}{'-':>6}{fit:>7.2f} |")
        print(out)

    print("\n=== POWER:HP EXCHANGE RATE ===")
    alphas = [x / 100 for x in range(90, 161, 2)]
    def best_alpha(p):
        return min(alphas, key=lambda a: ols(p, t, lambda r, a=a: (a * r["power"] + r["hp"]) / ((a + 1) / 2))[2].std())
    random.seed(11)
    boot = sorted(best_alpha([random.choice(dd) for _ in dd]) for _ in range(200))
    assert abs(best_alpha(dd) - ALPHA) < 0.2, "ALPHA drifted from the constant — update it"
    print(f"  1 power = {best_alpha(dd):.2f} HP   "
          f"(bootstrap median {boot[100]:.2f}, 90% CI [{boot[10]:.2f}, {boot[189]:.2f}], "
          f"P(power>HP) = {sum(1 for x in boot if x > 1)/len(boot):.2f})")

    print("\n=== FURTHEST FROM THE CURVE (weighted points) ===")
    ranked = sorted(zip(dd, res), key=lambda x: -x[1])
    def line(r, e):
        flags = [k + (str(v) if v > 1 else "") for k, v in r["kw"].items()]
        if str(r["unique"]) == "1": flags.insert(0, "unique")
        if "Force" in r["traitlist"]: flags.insert(0, "Force")
        return (f"  {e:+5.1f}  {r['id']:<8}{r['title'][:24]:<25}{r['arena'][0]} {r['cost']}c "
                f"{str(r['power'])+'/'+str(r['hp']):<5} [{r['aspects']}] {','.join(flags)}")
    print(" -- over --")
    for r, e in ranked[:10]:
        print(line(r, e))
    print(" -- under --")
    for r, e in ranked[-10:]:
        print(line(r, e))


if __name__ == "__main__":
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    main(args[0] if args else "cards.tsv", premier_only="--all" not in sys.argv)
