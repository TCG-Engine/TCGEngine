# Post-ban test panel v1 (frozen 2026-11-12)

The fixed opponent set for EVERY bot lever screen after the 2026-11-09 Premier bans (Neel, Chimaera, Koska, Aggressive Negotiations).
The owner chose the lists on 2026-11-12. Every lever screen was getting its own field, so results couldn't be compared with each other.
This set changes only when the owner says so; a new set is a new `postban-panel-v<N>/` folder.

| slot | role | deck | author | style | source |
|---|---|---|---|---|---|
| p1 | hyper aggro, ground go-wide | Set 9 Wicket Green (HMW_014 / HMW_023 Bright Tree Village) | VaNah | hyperaggro | swudb XsohWWMd |
| p2 | hyper aggro, space | Vader Yellow (JTL_006 / ASH_026 Freetown) | StarWarsDad | hyperaggro | swudb qctNrNuVH |
| p3 | Ahsoka Yellow | Ahsoka yellow Free Neel (ASH_009 / ASH_026) | FrowningPandora | hyperaggro | swudb HXJEMlBJcANw |
| p4 | indirect damage + Topple | Boba Blue Burn (JTL_009 / JTL_020) | Ninin | midrange | owner paste |
| p5 | fair midrange ramp | The Noxious Kerfuffle (IC27_004 Mace / HMW_021 Kachirho) | HolmesDM | midrange | swudb koMAIGbpYne |
| p6 | soft control, removal + wipes | Krennic Blue (Naboo) (LAW_008 / HMW_020) | Ninin | softcontrol | SWU Forge, Sando ×2 per owner image |
| p7 | control, long game | Everything You've Heard About Me is True (LAW_018 Lando / HMW_020) | DarthMyyk | softcontrol | transcribed from image |
| p8 | ping / Force engine | Maul Blue (Force) Icons (LOF_009 / LOF_020 Nightsister Lair) | Star Wars Dad | softcontrol | transcribed from image |

Owner rulings, 2026-11-12:
- "there is no soft aggro nowadays … three hyper aggros is fine until further notice."
- "there's no real hard control deck right now." p7 is a soft control list instead.

Every list was checked with no banned cards and every card ID known to the engine. IC27 cards are all on the IC27 Done list.

**Not for the Arenabot picker:** this folder is not in `SWU_PRECON_GROUPS`. The naming convention test doesn't cover it either; the `pN_` slot names are deliberate.

**Run a screen** (one-sided: the variant goes on our seat only; fixed seeds pv001–pv050 × both seats; about 5–15 min per arm):

```
docker exec -d -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
  bash SWUSim/DevTools/postban-panel/run-panel.sh <run-name> <our fixture> <our style> "<control variant>" "<on variant>"
python3 SWUSim/DevTools/postban-panel/paired.py .claude/tmp/postban-panel/runs/<run-name>
```

Each run also plays a mirror (our list against itself), unless our list is already a panel deck.

**Gotcha:** a `keep`-hook lever is inert on a soft-control seat against aggro leaders, because `resourcing3` resources by tiers first. Screen those on a midrange or aggro deck.
