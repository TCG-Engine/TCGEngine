#!/usr/bin/env python3
"""Find reprints in a SWU set and emit AppCore/SWU/Overrides.php case lines.

Usage:
    python3 find_reprints.py <SET_CODE>      e.g. ASH

A reprint = same Title + Subtitle (null/'' equivalent) + Cost + Aspects (as a set)
as a card from an earlier set. Tokens are excluded. The override target is the
EARLIEST printing of the card.
"""
import json
import os
import sys
from collections import defaultdict

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "..", ".."))
DATA = os.path.join(REPO, "SWUSim", "GeneratedCode", "cardArrayCache.json")


def code(e):
    try:
        return e["expansion"]["data"]["attributes"]["code"]
    except (KeyError, TypeError):
        return None


def aspects(e):
    try:
        return tuple(sorted(a["attributes"]["name"] for a in e["aspects"]["data"]))
    except (KeyError, TypeError):
        return ()


def norm_sub(e):
    s = e.get("subtitle")
    return s if s else None  # treat null and "" as the same


def typ(e):
    return (((e.get("type") or {}).get("data") or {}).get("attributes") or {}).get("value")


def key(e):
    return (e.get("title"), norm_sub(e), e.get("cost"), aspects(e))


def main():
    if len(sys.argv) != 2:
        sys.exit("usage: find_reprints.py <SET_CODE>  (e.g. ASH)")
    target = sys.argv[1].upper()

    cards = json.load(open(DATA))["cardArray"]

    # Derive set release order from expansion publishedAt.
    dates = {}
    for e in cards:
        ed = (e.get("expansion") or {}).get("data")
        if ed:
            a = ed["attributes"]
            dates[a["code"]] = a.get("releasedAt") or a.get("publishedAt") or a.get("createdAt")
    order = [c for c, _ in sorted(dates.items(), key=lambda x: str(x[1]))]
    rank = {c: i for i, c in enumerate(order)}
    if target not in rank:
        sys.exit(f"unknown set {target}; known: {', '.join(order)}")

    # Index every non-token printing by its identity key.
    by_id = {e["id"]: e for e in cards}
    idx = defaultdict(list)
    for e in cards:
        c = code(e)
        if c in rank and typ(e) != "Token":
            idx[key(e)].append((rank[c], c, e["id"]))

    set_cards = [e for e in cards if code(e) == target and typ(e) != "Token"]
    print(f"{target} non-token cards: {len(set_cards)}")

    reprints = []
    for e in sorted(set_cards, key=lambda x: x.get("cardNumber") or 0):
        chain = sorted(idx[key(e)])
        earlier = [m for m in chain if m[0] < rank[target]]
        if earlier:
            reprints.append((e["id"], earlier[0][2], [m[2] for m in chain]))

    print(f"Found {len(reprints)} reprints (tokens excluded):\n")
    for sid, tgt, chain in reprints:
        e, o = by_id[sid], by_id[tgt]
        name = e.get("title") + (f" - {norm_sub(e)}" if norm_sub(e) else "")
        print(f'    case "{sid}": return "{tgt}"; //{name}')
        print(f"        printings: {chain}")
        print(f"        match: title={e.get('title')!r} sub={norm_sub(e)!r} "
              f"cost={e.get('cost')} aspects={list(aspects(e))} "
              f"p/hp={e.get('power')}/{e.get('hp')}  (orig p/hp={o.get('power')}/{o.get('hp')})")
        print()


if __name__ == "__main__":
    main()
