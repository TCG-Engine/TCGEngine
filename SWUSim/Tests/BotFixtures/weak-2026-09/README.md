# Weak fixtures — deliberately bad decks (2026-09-15)

Nine decks built to be worse than the meta, for the **deck-advantage conversion test**: a strong pilot beats a
clearly worse deck nearly every time, so the rate at which our bots fail to is a direct measure of piloting quality.
Unlike a bot-vs-tournament comparison, this metric has no field-skew confound — both sides are our own bots, and the
only asymmetry is deck quality.

⚠ **None of these is Premier legal**, and none is tournament-backed. The three owner lists use an HMW base; the six
starter decks are pre-JTL (SOR / SHD / TWI), built to a low power level before JTL's "Spotlight" decks raised it.
**Never add this set to a strength gate or to `meta-2026-09*`** — gates are pairwise, and these decks are not the
field we are trying to be strong against.

Sources: the owner (ninin) supplied the three category decks and all six starters. Styles are auto-derived from
deck shape and NOT reviewed. Sizes are checked by `SWUSim/DevTools/check_fixture_sizes.php`.

| File | Category | Cards | Avg cost | Removal | Style | Beat a meta deck |
|---|---|---:|---:|---:|---|---:|
| `neutralunits` | all no-aspect units | 51 | 2.94 | 0 | aggro | 0 / 12 |
| `starter_shd_gideon` | starter (SHD) | 50 | 2.98 | 3 | aggro | 0 / 12 |
| `starter_shd_mando` | starter (SHD) | 50 | 2.52 | 1 | normal | 0 / 12 |
| `starter_twi_grievous` | starter (TWI) | 50 | 3.06 | 0 | normal | 1 / 12 |
| `badcurve` | bad curve | 54 | 6.94 | 12 | control | 2 / 12 |
| `noremoval` | no removal | 50 | 3.42 | 0 | normal | 3 / 12 |
| `starter_sor_luke` | starter (SOR) | 50 | 2.86 | 3 | aggro | 3 / 12 |
| `starter_twi_ahsoka` | starter (TWI) | 50 | 2.86 | 0 | aggro | 5 / 12 |
| `starter_sor_vader` | starter (SOR) | 50 | 3.16 | 0 | aggro | **6 / 12** |

**Baseline 2026-09-15: the bots convert 81%** (weak decks won 20 of 108 against vader_yellow,
krennic_splash and maul_blueforce, both seats, 2 seeds).

★ **The two outliers are the finding.** A 2024 SOR starter deck goes EVEN with the September meta in bot hands, and
the TWI Ahsoka starter takes 42%. Starter decks are simple: cheap units, attack. That our bots keep pace with the
meta decks while piloting them says the gap is in the complicated decks — the same "does not stabilise under early
pressure" weakness that sank Krennic (23% over 48 games). Re-run this test after the control/stabilise work; these
two rows should fall first.

**Re-run it:**

    docker cp /tmp/conv.sh otmtcge-swusim-web-server-1:/tmp/conv.sh && docker exec otmtcge-swusim-web-server-1 bash /tmp/conv.sh

**Conversion caveats.** Two starter lists needed hand-resolution: several SOR/TWI legacy UUIDs are in none of our
maps (card dictionary, reprint map, leader-unit map), and one export listed Tarkin and Palpatine by their LEADER
ids (`SOR_007` / `SOR_006`) when the deck runs the UNIT printings (`SOR_084` / `SOR_135`). Resolving a starter list
by card NAME is the reliable path, preferring the non-Leader printing except for the deck's own leader.
