"""Supplement TCGdex's missing Tera tag from the PokemonTCG data provider.
Match printings by name, HP, types and attack names/costs, never Pokemon name alone.
Refresh after importing new cards. Source revision is pinned in the resulting snapshot.
"""
import concurrent.futures, json, re, unicodedata, urllib.request
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
def fetch(url):
    req=urllib.request.Request(url,headers={"User-Agent":"TCGEngine-PokeSim"})
    with urllib.request.urlopen(req,timeout=45) as r:return json.load(r)
def norm(s):return re.sub(r"[^a-z0-9]", "", unicodedata.normalize("NFKD",str(s)).encode("ascii","ignore").decode().lower())
def signature(c):
    if any(not isinstance(a,dict) or not a.get("name") for a in c.get("attacks") or []):return None
    types=c.get("types") or []
    if isinstance(types,str):types=types.split(",")
    return (norm(c["name"]),int(c.get("hp") or 0),tuple(sorted(map(norm,types))),tuple((norm(a.get("name", "")),tuple(sorted(map(norm,a.get("cost") or [])))) for a in c.get("attacks") or []))
sha=fetch("https://api.github.com/repos/PokemonTCG/pokemon-tcg-data/commits/master")["sha"]
tree=fetch(f"https://api.github.com/repos/PokemonTCG/pokemon-tcg-data/git/trees/{sha}?recursive=1")
paths=[e["path"] for e in tree["tree"] if e["path"].startswith("cards/en/sv") and e["path"].endswith(".json")]
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
    rows=list(pool.map(lambda path:fetch(f"https://raw.githubusercontent.com/PokemonTCG/pokemon-tcg-data/{sha}/{path}"),paths))
refs={signature(c):c["id"] for cards in rows for c in cards if "Tera" in c.get("subtypes",[])}
if not refs:raise RuntimeError("No source Tera metadata")
cache=json.loads((ROOT/"PokeSim/GeneratedCode/cardArrayCache.json").read_text(encoding="utf-8"))
matched={c["id"]:refs[signature(c)] for c in cache["cardArray"] if signature(c) in refs}
if not all(id in matched for id in ["sv06-130","sv06-106","sv06-025"]):raise RuntimeError("Missing known Tera printings")
if any(id in matched for id in ["sv07-118","me02-084","me04-022"]):raise RuntimeError("False Tera classification")
out={"source":"https://github.com/PokemonTCG/pokemon-tcg-data","revision":sha,"matching":"name + HP + types + ordered attack names and Energy costs","cards":dict(sorted(matched.items()))}
(ROOT/"PokeSim/Custom/TeraCards.json").write_text(json.dumps(out,indent=2)+"\n",encoding="utf-8")
print(f"Classified {len(matched)} Tera printings from {len(refs)} distinct card designs ({sha[:12]})")

