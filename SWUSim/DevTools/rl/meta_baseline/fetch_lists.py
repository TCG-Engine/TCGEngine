#!/usr/bin/env python3
"""Meta-baseline FETCH (skill `swusim-ash-meta-bot-run`). usage: fetch_lists.py <export.json> <lists dir> [workers=2]

Fetch EVERY entry's decklist contents (not just one per archetype) into decklists/<DecklistId>.json.

    python3 scripts/fetch_all_lists.py [workers=2]

Why: the fixtures are ONE list per archetype, so they can say whether a card appears in an archetype at all but
not how often — e.g. whether Hyperspace Disaster is in 90% or 10% of Krennic lists. Card-level relevance
questions (owner, 2026-09-18: "not all of these cards are relevant … verify by analyzing the tournament deck
lists") need every list. The events cache holds standings and pairings only, never card contents.

Goes through the LOCAL APIs/MeleeLinkToJson.php (rootName=SWUDeck), so names resolve with the same
FindCardSetCode the fixtures used — including the Zeb Orellios alias fixed 2026-09-18. Resumable: a list
already on disk is skipped. Throttled, because every request reaches melee.gg. Read-only; writes only under
decklists/ (gitignored with the rest of docs/superpowers/).
"""
import json, os, sys, time, urllib.parse, urllib.request
from concurrent.futures import ThreadPoolExecutor

OUT = sys.argv[2]
API = 'http://localhost:3100/TCGEngine/APIs/MeleeLinkToJson.php?rootName=SWUDeck&meleeLink='
WORKERS = int(sys.argv[3]) if len(sys.argv) > 3 else 2
os.makedirs(OUT, exist_ok=True)
ids = [[d['t'], d['p'], d['src']] for d in json.load(open(sys.argv[1]))['decks'].values() if d.get('src')]


def fetch(entry):
    tid, user, did = entry
    path = os.path.join(OUT, f'{did}.json')
    if os.path.exists(path):
        return 'cached'
    url = API + urllib.parse.quote(f'https://melee.gg/Decklist/View/{did}', safe='')
    for attempt in range(3):
        try:
            body = urllib.request.urlopen(url, timeout=60).read().decode()
            j = json.loads(body)
            if 'error' in j:
                raise RuntimeError(j['error'])
            j['_tid'], j['_user'], j['_id'] = tid, user, did
            json.dump(j, open(path + '.tmp', 'w'))
            os.replace(path + '.tmp', path)   # atomic: a half-written list is never read
            time.sleep(0.4)
            return 'ok'
        except Exception as e:
            err = e
            time.sleep(3)
    return f'FAIL {did}: {err}'


done = ok = cached = 0
fails = []
with ThreadPoolExecutor(WORKERS) as ex:
    for r in ex.map(fetch, ids):
        done += 1
        if r == 'ok': ok += 1
        elif r == 'cached': cached += 1
        else: fails.append(r)
        if done % 100 == 0:
            print(f'{done}/{len(ids)}  ok {ok}  cached {cached}  fail {len(fails)}', flush=True)
print(f'DONE {done}/{len(ids)}  ok {ok}  cached {cached}  fail {len(fails)}', flush=True)
for f in fails[:20]:
    print('  ' + f)
