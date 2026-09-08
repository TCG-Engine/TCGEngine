#!/usr/bin/env python3
"""
Cost-curve Phase 2 — price printed EFFECTS against the Phase 1 stat curve.

    python3 fit_effects.py cards.tsv

A unit's ability is paid for in stats: the same regression that priced Sentinel at 1.5 points
prices "When Played: deal 2 damage" the same way, as a coefficient on the stat line.

UNITS ONLY.  Events carry no stat line and would have to enter on a shared cost->value curve;
fitting that produced an event cost slope of ~0 and dragged the unit terms with it, so events
are left to Phase 3.  See SWUSim/docs/cost-curve.md.
"""
import re, sys, collections
import numpy as np
from fit import load, clean_pool, terms, ols, NON_PREMIER, ALPHA, DIRTY
from effects import classify_card

# Families whose value scales with the printed N; everything else is binary.
SCALED = {"dmg_unit", "dmg_unit_arena", "dmg_base", "dmg_friendly", "draw", "heal_unit",
          "heal_base", "discard_choose", "discard_random", "give_exp", "give_shield",
          "give_advantage", "give_weakness",
          # stat riders: n is the SUM of the printed pair, so the coefficient is per stat
          "self_buff", "other_buff", "other_debuff"}
# These carry n as "KEYWORD:N"; the regressor value is that keyword's own Phase 1 price x N, so
# the fitted coefficient reads as a FRACTION of list price for a granted/conditional keyword.
KEYWORD_SCALED = {"gains_keyword", "gains_keyword_other"}
# Printed body of each created token, for the structural check: is a token worth its stats?
TOKEN_BODY = {                       # (power, hp, arena, extra keyword points)
    "create_battle_droid":  (1, 1, "Ground", 0.0),
    "create_clone_trooper": (2, 2, "Ground", 0.0),
    "create_beast":         (3, 3, "Ground", 0.0),
    "create_mandalorian":   (2, 2, "Ground", None),   # + Shielded
    "create_spy":           (0, 2, "Ground", None),   # + Raid 2
    "create_tie_fighter":   (1, 1, "Space",  0.0),
    "create_x_wing":        (2, 2, "Space",  0.0),
}
MIN_N = 3   # families thinner than this are reported but not trusted


def effect_units(rows):
    """Premier units with ability text that classifies COMPLETELY."""
    out = []
    for r in rows:
        if r["type"] != "Unit" or "_T" in r["id"] or r["cost"] is None:
            continue
        if r["set"] in NON_PREMIER or not r["resid"].strip():
            continue
        if any(k in r["kw"] for k in DIRTY):
            continue
        if r["power"] == 0 and r["hp"] == 0:
            continue          # a 0/0 printed line carries no information about anything
        if re.search(r"costs? \d+ resources? less to play", r["resid"]):
            continue          # a self-discount moves effective cost; see Exploit
        cl = classify_card(r["resid"])
        if cl:
            r["clauses"] = cl
            out.append(r)
    return out


def build_terms(pool, families, kw_price=None):
    t = terms()
    kw_price = kw_price or {}
    def amount(r, fam):
        tot = 0
        for c in r.get("clauses", []):
            if c["family"] != fam:
                continue
            if fam in KEYWORD_SCALED:
                name, _, n = str(c["n"]).partition(":")
                tot += kw_price.get(name, 1.0) * int(n or 1)
            elif fam in SCALED or fam.startswith("create_"):
                tot += c["n"]
            else:
                tot += 1
        return tot
    for f in families:
        t["E:" + f] = (lambda f: (lambda r: amount(r, f)))(f)
    # per-clause modifiers, WhenPlayed / mandatory / unconditional as the reference level
    for trig in ("OnAttack", "WhenDefeated", "Action"):
        t["M:" + trig] = (lambda tr: (lambda r: sum(1 for c in r.get("clauses", [])
                                                    if c["trigger"] == tr)))(trig)
    t["M:optional"] = lambda r: sum(1 for c in r.get("clauses", []) if c["optional"])
    t["M:conditional"] = lambda r: sum(1 for c in r.get("clauses", []) if c["conditional"])
    t["M:dual_trigger"] = lambda r: sum(1 for c in r.get("clauses", []) if c.get("dual"))
    return t


def run(pool, families, label, kw_price=None):
    t = build_terms(pool, families, kw_price)
    w = lambda r: (ALPHA * r["power"] + r["hp"]) / ((ALPHA + 1) / 2)
    b, se, res, r2 = ols(pool, t, w)
    print(f"\n=== {label}   n={len(pool)}  R^2={r2:.4f}  residual sd={res.std():.3f} ===")
    print(f"{'term':<22}{'points':>8}{'se':>7}{'t':>7}   n")
    counts = collections.Counter()
    for r in pool:
        for c in r.get("clauses", []):
            counts[c["family"]] += 1
    for nm, bb, ss in zip(t, b, se):
        if not nm.startswith(("E:", "M:")):
            continue
        n = counts.get(nm[2:], "")
        flag = "  *" if abs(bb / ss) > 2 else ("  ~" if abs(bb / ss) > 1.5 else "")
        # effects are paid FOR, so report the price (sign-flipped)
        print(f"{nm:<22}{-bb:>8.2f}{ss:>7.2f}{-bb/ss:>7.2f}{flag}   {n}")
    return dict(zip(t, b)), dict(zip(t, se)), res, dict(zip(t, b))


def token_check(coefs, ses, base_coefs):
    """Is a created token worth the curve value of the body it puts on the board?"""
    print("\n=== created tokens: fitted price vs the token's own body ===")
    print(f"{'token':<22}{'body':>7}{'body pts':>10}{'fitted':>9}{'se':>6}")
    for fam, (p, h, arena, extra) in TOKEN_BODY.items():
        k = "E:" + fam
        if k not in coefs:
            continue
        pts = (ALPHA * p + h) / ((ALPHA + 1) / 2)
        if extra is None:                      # Mandalorian (Shielded) / Spy (Raid 2)
            pts += -base_coefs["Shielded"] if fam == "create_mandalorian" else -2 * base_coefs["Raid_N"]
        print(f"{fam[7:]:<22}{f'{p}/{h}':>7}{pts:>10.2f}{-coefs[k]:>9.2f}{ses[k]:>6.2f}")


def main(path):
    rows = load(path)
    _, base = clean_pool(rows)
    eu = effect_units(rows)
    # Phase 1 keyword prices, needed to weight the "gains <keyword>" families.
    kb, _, _, _ = ols(base, terms(), lambda r: (ALPHA * r["power"] + r["hp"]) / ((ALPHA + 1) / 2))
    kw_price = {k: -v for k, v in zip(terms(), kb) if k in
                ("Sentinel", "Ambush", "Overwhelm", "Shielded", "Grit", "Hidden", "Saboteur")}
    kw_price["Raid"] = -dict(zip(terms(), kb))["Raid_N"]
    kw_price["Restore"] = -dict(zip(terms(), kb))["Restore_N"]
    fams = sorted({c["family"] for r in eu for c in r["clauses"]})
    print(f"baseline (keyword-only) units: {len(base)}")
    print(f"classified ability units:      {len(eu)}")
    print(f"effect families:               {len(fams)}")
    print("\nNOTE: 'points' is what the effect COSTS the card in stats. Higher = more valuable.")
    print("\nPhase 1 keyword prices used to weight the 'gains <keyword>' families: "
          + ", ".join(f"{k} {v:.2f}" for k, v in sorted(kw_price.items())))
    coefs, ses, res, base_only = run(base + eu, fams, "EFFECT PRICES (units only)", kw_price)
    token_check(coefs, ses, base_only)
    print(f"\nresidual sd, keyword-only units: {np.std([e for r, e in zip(base+eu, res) if r in base]):.3f}")
    print(f"residual sd, ability units:      {np.std([e for r, e in zip(base+eu, res) if r not in base]):.3f}")


if __name__ == "__main__":
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    main(args[0] if args else "cards.tsv")
