# SWU cost curve

**Phase 1 — unit stat baselines: complete.** Ground/space curves, aspect locks, keyword prices,
power:HP rate.
**Phase 2 — printed effect prices: first pass.** 21 effect families priced against the Phase 1
curve, plus trigger/optional/conditional modifiers.
**Flexibility and drawbacks: priced.** Bounty, Plot and Smuggle are now model terms rather than
exclusions; Exploit is measured against the curve rather than inside it.
**Phase 3 — the event line: derived from six token-making events**, and it closes to ±0.04 points,
which independently validates the Phase 1 and Phase 2 numbers.
**Rarity: checked.** Phase 1 has 1 Legendary in 259 and Phase 2 has none, so neither can be skewed
by pushed rares; controlling for rarity moves the effect prices by ≤0.13.
**Not done:** a general event curve, upgrades, leaders, bases, and the ~85% of ability text outside
the 21 families. See [Open phases](#open-phases).

**Scope: Premier products only.** `IBH` (intro decks) and `TS26` (Twin Suns starter) are excluded —
IBH prints a full point under curve by design, TS26 runs slightly hot because the format is
singleton. Excluding them moved Phase 1 R² 0.953 → 0.966 and every coefficient outward coherently.
`fit.py cards.tsv --all` puts them back. Pool: SOR SHD TWI JTL LOF SEC LAW ASH HMW IC27, 2026-09-08.

## Reproducing

```bash
docker exec otmtcge-swusim-web-server-1 php -d xdebug.mode=off \
  /var/www/html/TCGEngine/SWUSim/DevTools/cost-curve/dump-cards.php > cards.tsv
python3 SWUSim/DevTools/cost-curve/fit.py         cards.tsv   # Phase 1: stats
python3 SWUSim/DevTools/cost-curve/fit_effects.py cards.tsv   # Phase 2: effects
```

`dump-cards.php` reads the **generated** dictionaries, so a new preview wave only appears after
`php zzCardCodeGenerator.php rootName=SWUSim`.

## Units

Everything is denominated in **points**, where 1 point ≈ 1 printed stat. Cost, arena, aspect pips,
uniqueness, keywords and abilities are all priced in that one unit, so they can be traded against
each other. Power is worth more than HP, so the response is **`(1.32 × power + HP)` normalised** —
see [Power is worth more than HP](#power-is-worth-more-than-hp).

---

# Phase 1 — the stat curve

Fit on Premier units whose printed box contains **nothing but keywords**: 284 units, **273 after
removing reprints**.

```
n = 273    R² = 0.974    residual sd = 0.46 points
           (unweighted power+HP for comparison: R² = 0.969, sd = 0.50)
```

Excluded on purpose: units with ability text (Phase 2), units carrying **Piloting, Exploit or
Support**, reprints, and everything that isn't a unit. **Bounty, Plot and Smuggle are not
excluded** — Bounty is a drawback and Plot/Smuggle are flexibility, and all three are priced. See
[Drawbacks](#drawbacks) and [Flexibility keywords](#flexibility-keywords-plot-and-smuggle).

## The baseline

**Ground:  points = 2.07 + 1.32 × cost + 0.063 × cost²**
**Space:   points = 1.20 + 1.39 × cost + 0.063 × cost²**

for a keyword-free, non-unique, **aspect-free** unit. Add the modifiers below.

| cost | G 0 pip | G 1 pip | G 2 pip | G 3 pip | S 0 pip | S 1 pip | S 2 pip | S 3 pip |
|-----:|--------:|--------:|--------:|--------:|--------:|--------:|--------:|--------:|
| 0 | 2.1 | 2.8 | 3.6 | 4.4 | 1.2 | 2.0 | 2.7 | 3.5 |
| 1 | 3.5 | 4.2 | 5.0 | 5.8 | 2.6 | 3.4 | 4.2 | 5.0 |
| 2 | 5.0 | 5.7 | 6.5 | 7.3 | 4.2 | 5.0 | 5.8 | 6.5 |
| 3 | 6.6 | 7.4 | 8.1 | 8.9 | 5.9 | 6.7 | 7.5 | 8.2 |
| 4 | 8.4 | 9.1 | 9.9 | 10.7 | 7.8 | 8.5 | 9.3 | 10.1 |
| 5 | 10.3 | 11.0 | 11.8 | 12.6 | 9.7 | 10.5 | 11.3 | 12.0 |
| 6 | 12.3 | 13.0 | 13.8 | 14.6 | 11.8 | 12.6 | 13.3 | 14.1 |
| 7 | 14.4 | 15.2 | 16.0 | 16.7 | 14.0 | 14.8 | 15.5 | 16.3 |
| 8 | 16.7 | 17.5 | 18.2 | 19.0 | 16.3 | 17.1 | 17.9 | 18.7 |
| 9 | 19.1 | 19.9 | 20.6 | 21.4 | 18.8 | 19.6 | 20.3 | 21.1 |

*("n pip" = n **basic** aspect pips; alignment pips are cheaper.)*

Observed vs fitted, every card normalised back to a 0-pip keyword-free unit:

| cost | G n | G obs | G sd | G fit | S n | S obs | S sd | S fit |
|-----:|----:|------:|-----:|------:|----:|------:|-----:|------:|
| 1 | 28 | 3.46 | 0.46 | 3.45 | 9 | 2.89 | 0.32 | 2.65 |
| 2 | 47 | 4.86 | 0.36 | 4.96 | 20 | 4.13 | 0.43 | 4.22 |
| 3 | 48 | 6.65 | 0.43 | 6.60 | 16 | 5.78 | 0.30 | 5.92 |
| 4 | 36 | 8.40 | 0.50 | 8.36 | 7 | 7.75 | 0.29 | 7.75 |
| 5 | 18 | 10.29 | 0.50 | 10.25 | 12 | 9.50 | 0.71 | 9.71 |
| 6 | 16 | 11.87 | 1.04 | 12.27 | 9 | 11.96 | 0.42 | 11.79 |
| 7 | 2 | 14.19 | 0.44 | 14.41 | 2 | 14.00 | 0.73 | 14.00 |

Cost 0, 8 and 9 have one card each and are extrapolation, not measurement.

## The curve is convex — cost buys more at the top

The quadratic term is `+0.064` at **t = 6.5**; dropping it costs 0.04 points of residual sd and
leaves a U-shaped residual by cost. It survives restricting to cost ≤ 6 (t = 4.2), so it is not an
artifact of the single 8- and 9-drops.

A free dummy per cost gives the marginal points each extra resource buys:

| step | points | n | | step | points | n |
|---|---:|---:|---|---|---:|---:|
| 0 → 1 | +1.52 | 37 | | 4 → 5 | +1.85 | 30 |
| 1 → 2 | **+1.36** | 67 | | 5 → 6 | +1.92 | 25 |
| 2 → 3 | +1.73 | 64 | | 6 → 7 | +2.12 | 4 |
| 3 → 4 | +1.82 | 43 | | 7 → 8 | +2.19 | 1 |

**~1.35–1.75 points per cost at the bottom, ~2.0–2.1 at the top.** Big bodies are paid a premium for
the tempo risk of committing everything to one card. The wrinkle is the **1 → 2 step at 1.36, the
flattest rung** — 2-drops are the least stat-efficient slot in the game, which matters because
they carry a lot of the game's text.

## Ground vs space

A space unit gets **~0.87 fewer points than a ground unit of the same cost**, and that gap is
**flat** — with curvature in the model, `cost × Space` falls to t = 1.6 and is not significant.

*"Space is cost × 2 + 1"* holds for **2-pip** space in the 2–5 range (5.7 at cost 2, 7.5 at cost 3,
11.3 at cost 5). `JTL_095` Phoenix Squadron A-Wing and `SEC_161` Contraband Starhopper both land
there. It over-states aspect-free space, which is closer to `2 × cost`.

### Did space get power-crept when JTL landed?

Not in the stat lines, and not in the ability budgets either — but the curve is measuring the wrong
thing to answer the question, so read this as "the printed rate did not move", not "nothing changed".

**Stat lines.** Adding a `Space x post-JTL` interaction gives **+0.15 (t = 1.15)** and moves R^2 by
0.0001. Space's normalised stat lines pre-JTL (SOR/SHD/TWI) versus JTL-and-later, per cost:

| cost | pre n | pre | JTL+ n | JTL+ | diff | | ground diff (control) |
|---:|---:|---:|---:|---:|---:|---|---:|
| 1 | 3 | 2.78 | 6 | 2.94 | +0.16 | | −0.04 |
| 2 | 6 | 4.49 | 14 | 4.13 | −0.36 | | +0.06 |
| 3 | 5 | 5.83 | 11 | 5.76 | −0.08 | | +0.07 |
| 4 | 2 | 7.44 | 5 | 7.88 | +0.45 | | +0.41 |
| 5 | 4 | 9.44 | 8 | 9.80 | +0.37 | | +0.36 |

**Ground moved the same way, and at 6 cost it moved more (+0.79).** Whatever drift there is after
TWI is the general set drift documented under [Set drift](#set-drift) — TWI is the stingiest set in
the pool at −0.32 — not something that happened to space.

**Ability budgets.** Same test on the 1,119 Premier ability units, regressing text budget on cost,
arena, era and their interaction: `Space x post-JTL` = **−0.075 (t = −0.34)**. Both arenas' budgets
fell slightly after TWI (ground −0.31, space −0.28). No space-specific generosity there either.

**What JTL actually changed was volume.**

| set | ground | space | space share | space avg cost | ground avg cost |
|---|---:|---:|---:|---:|---:|
| SOR | 110 | 38 | 26% | 4.11 | 3.63 |
| SHD | 124 | 36 | 22% | 4.00 | 3.57 |
| TWI | 118 | 32 | 21% | 4.22 | 3.99 |
| **JTL** | **76** | **91** | **54%** | 4.18 | **2.99** |
| LOF | 129 | 37 | 22% | 4.38 | 3.68 |
| SEC | 129 | 42 | 25% | 4.12 | 3.69 |
| LAW | 143 | 39 | 21% | 4.90 | 3.66 |
| ASH | 126 | 53 | 30% | 4.36 | 3.59 |

Three sets had produced about 106 space units between them; **JTL added 91 on its own** and is the
only set that is majority space. Its ground cards are also unusually cheap — a 2.99 average against
3.5–4.0 everywhere else — which reads as cheap ground support for a space deck rather than competing
ground threats.

⚠ **Two blind spots make this a partial answer.** The model cannot see **depth** — it measures the
rate at which cost buys stats on a card, never how many playable cards a deck has access to, and
"space was weak" is mostly a claim about the latter. And it cannot see **Piloting**, a JTL mechanic
that makes space units better without touching a printed stat; Piloting is one of the two keywords
still excluded from every pool. If JTL improved space through its printed numbers, this analysis
would have found it. It did not, so the mechanism was somewhere the analysis cannot reach.

## Aspect locks

| pip | points bought | t |
|---|---:|---:|
| one **basic** aspect (Command / Aggression / Cunning / Vigilance) | **+0.77** | 11.0 |
| one **alignment** aspect (Heroism / Villainy) | **+0.52** | 8.2 |

The standard 2-pip card (one basic + one alignment) is forgiven **+1.29 points** — about one extra
stat — and a double-basic card (`HMW_174` Maul, Aggression × 2) gets **+1.54**.

Per basic aspect: Command +0.86, Aggression +0.82, Cunning +0.73, Vigilance +0.70 (all t > 7). The
0.16 spread is within the standard errors (~0.10), so treat the four as equal for now.

## Keyword prices

Points of stats a keyword costs. All significant at t > 4.

| keyword | points | prior |
|---|---:|---|
| Ambush | **1.66** | 1 |
| Shielded | **1.65** | 2 |
| Sentinel | **1.55** | 1 |
| Grit | **1.34** | — |
| Raid | **0.85** per point of Raid | free if on-aspect |
| Restore | **0.80** per point of Restore | 1 for Restore 1, free if on-aspect |
| Hidden | **0.67** | free if on-aspect |
| Saboteur | **0.55** | 1 |
| Overwhelm | **0.45** | 1, free if on-aspect |
| *Bounty* — a **drawback**, so it pays the card | *+0.75* | — |

- **Ambush and Sentinel are premium keywords**, not 1-pointers; the top four sit inside a 0.32 band,
  so Shielded is not the outlier you'd expect.
- **Saboteur costs 0.55.** The `LAW_230` / `SEC_199` pair shows it: one alignment pip (+0.52) plus
  Saboteur (+0.55) ≈ the 1.0-point gap between them.
- **Restore is linear in N** — Restore 2 costs 1.59, not "1 or 2".
- **Overwhelm at 0.45 is the cheapest keyword in the game.**

### "Free if tied to aspect" does not hold

Refitting with an interaction for each keyword on its natural aspect moves R² 0.9744 → 0.9762.

| interaction | coef | t | | interaction | coef | t |
|---|---:|---:|---|---|---:|---:|
| Overwhelm @ Aggression | +0.22 | +1.03 | | Grit @ Aggression | −0.16 | −0.69 |
| Ambush @ Aggression | +0.10 | +0.20 | | Saboteur @ Cunning | −0.35 | −1.75 |
| Shielded @ Vigilance | −0.03 | −0.14 | | Sentinel @ Vigilance | −0.37 | **−2.45** |
| Raid @ Aggression | −0.05 | −0.61 | | Hidden @ Cunning | −0.57 | **−2.93** |
| Restore @ Vigilance | −0.06 | −0.55 | | | | |

A *positive* coefficient would mean the keyword is refunded on-aspect. The two positives are the
weakest results in the table; **the only two significant results are both negative** — on-aspect
Hidden and Sentinel cost *more*. Whether that small premium is real or two draws from nine tests, it
is certainly not a discount. **Overwhelm and Raid 1 look cheap because they are cheap for everyone**,
not because Aggression gets them free.

## Power is worth more than HP

```
α = 1.32     bootstrap median 1.34, 90% CI [1.26, 1.44], P(α > 1) = 1.00
```

**1 power ≈ 1.3 HP.** All 200 bootstrap resamples put α above 1. Practically: **count power at 1.14
and HP at 0.86**. Moving one stat from HP to power costs **0.28 points**, so a 4/1 body is ~0.85
points more expensive than a 1/4 at the same cost — `ASH_190` Peridea Bandit (2c 4/1) is −1.3
against an unweighted curve and −0.83 against this one.

Weighting is worth as much as the quadratic, and they stack:

| model | residual sd | R² |
|---|---:|---:|
| linear cost, `power + HP` | 0.537 | 0.9657 |
| linear cost, weighted | 0.501 | 0.9702 |
| quadratic cost, `power + HP` | 0.509 | 0.9692 |
| **quadratic cost, weighted** | **0.464** | **0.9744** |

## Uniqueness, and traits

**unique = +0.77 points** (t = 5.9) — about three quarters of a stat.

**No trait shows a premium.** Force measured −0.05 (t = −0.4) and has been dropped from the model.
Vehicle, Trooper, Fighter, Imperial, Rebel, Underworld, Fringe and Republic all measured zero too,
as did raw trait *count*. Traits appear to be free.

### On Gungi

`LOF_093` Gungi (2c 2/5) is 6.59 weighted points against a fit of **7.03** — slightly *under* curve:

```
4.96 (ground, 2 cost) + 0.77 (Command) + 0.52 (Heroism) + 0.77 (unique) = 7.03
```

The comparison to `HMW_163` Champion of Endor (2c 3/3, same lock) is **unique vs not** — 0.79 points
— plus the power/HP split, since 2/5 is the cheap side of the exchange rate and 3/3 the expensive
side. Both land 0.25–0.45 under curve. What reads as over-statting is the uniqueness rebate spent
entirely on HP.

`SEC_044` Populist Champion (3c 3/5, −0.17) vs `LOF_096` Obi-Wan Kenobi (3c 3/5, unique) prices the
same way — Obi-Wan is **0.95 under** his 8.66 curve, which is what his *conditional* Sentinel costs.
Full Sentinel is 1.55, so a conditional one runs about 61% of list.

## Named baselines, checked

| card | | line | weighted | fit | Δ |
|---|---|---|---:|---:|---:|
| `LOF_254` Porg | G 0c | 1/1 | 2.00 | 2.07 | −0.07 |
| `HMW_268` Offworld Jawa | G 1c | 2/1 | 3.14 | 3.45 | −0.31 |
| `LOF_256` Gifted Urchin | G 2c | 1/4 | 4.59 | 4.96 | −0.37 |
| `ASH_261` Noti Mobile Pod | G 3c | 3/4 | 6.86 | 6.60 | +0.26 |
| `LAW_260` Seasoned Tracker | G 4c Hidden | 4/4 | 8.00 | 7.69 | +0.31 |
| `SEC_262` Ando Commission | G 4c Sentinel | 4/3 | 7.14 | 6.82 | +0.32 |
| `LAW_259` Cartel Heavy Fighter | S 3c Overwhelm | 3/2 | 5.14 | 5.48 | −0.34 |
| `TWI_253` Headhunter Squadron | S 2c | 1/4 | 4.59 | 4.22 | +0.37 |
| `JTL_258` Corellian Freighter | S 5c Sentinel | 4/4 | 8.00 | 8.16 | −0.16 |
| `LAW_263` Kessel Hulk | S 7c Sentinel | 5/7 | 11.72 | 12.45 | −0.73 |

Nine of ten within 0.4. `LAW_263` is the miss and sits where the pool is thinnest (two 7-cost space
units), so the top of the space curve is the least trustworthy region of the table.

## Set drift

| set | n | mean Δ | | set | n | mean Δ |
|---|---:|---:|---|---|---:|---:|
| ASH | 31 | +0.22 | | LAW | 46 | −0.01 |
| HMW | 16 | +0.19 | | SHD | 31 | −0.03 |
| JTL | 20 | +0.19 | | SEC | 33 | −0.08 |
| LOF | 45 | +0.01 | | SOR | 29 | −0.12 |
| | | | | TWI | 22 | −0.32 |

The whole Premier range is **0.54 points wide** — barely half a stat across nine sets and two years.
As set dummies, TWI (−0.32, t = −3.0), ASH (+0.23, t = 2.5) and JTL (+0.24, t = 2.0) clear
significance, but a quarter of a stat is under half the design increment and it does not trend with
release date. **There is no power creep in vanilla bodies**, and **HMW (preview) at +0.19 (t = 1.9)
is inside the band.**

For reference the excluded products sat at **IBH −1.04** and **TS26 +0.36** against an all-sets fit
— IBH alone is 0.7 below TWI, and the IBH-to-TS26 spread is more than twice the entire Premier
range.

---

# Phase 2 — effect prices

Same regression, extended: the 273 keyword-only units keep the curve identified, and **286 units
whose ability text classifies completely** carry the effect coefficients.

```
n = 559    R² = 0.935
residual sd:  0.53 on keyword-only units   ·   0.88 on ability units
```

Abilities are still priced more loosely than stats — 0.88 against 0.53 — but the gap has closed a
lot since the classifier was widened (it was 1.11 against 0.52 at 146 units). Treat the numbers
below as a ranking with magnitudes, not a lookup table.

## Coverage, and what it costs

**286 of 1,080** Premier ability units (26%) classify, covering **28% of all printed clauses**. A
clause must match a known family **for its entire length** — a prefix match is a trap. `SOR_033`
Death Trooper reads *"Deal 2 damage to a friendly ground unit and 2 damage to an enemy ground
unit"*; an earlier unanchored version matched the friendly half, discarded the enemy half, and drove
`deal N damage to a unit` to a price of **0.04 points**. Anchoring every pattern and splitting
friendly-target damage into its own family fixed it. Any clause the classifier does not fully
recognise disqualifies the whole card.

Three things are deliberately *not* families, each for a reason the model learned the hard way:

- **`for each` riders.** `SOR_118` 97th Legion is a 7-cost **0/0** whose entire body comes from
  *"+1/+1 for each …"*. Its printed line carries no information, and including it put a −12.8
  residual into the fit. Any clause containing "for each" is unclassified, and any printed 0/0 line
  is dropped outright.
- **`this unit costs N resources less to play`.** A self-discount moves the card's **effective
  cost**, exactly like Exploit, so fitting it as a stat rider mis-specifies the card — `TWI_098`
  Republic Defense Carrier (11 cost, 6/7) read 6.3 points off on its own.
- **Five of the originally requested families have no clean unit at all** and stay unpriced:
  `capture a unit` (both forms), `Strike True / power strike` (both forms), and
  `discard N at random`. Every printed instance is bundled with other text.

### What widening the classifier bought

| | units | clauses | effect families | ability-side sd |
|---|---:|---:|---:|---:|
| first pass | 146 | 14% | 21 | 1.11 |
| **after widening** | **286** | **28%** | **28** | **0.88** |

The sample nearly doubled **and** the fit got tighter, which is the outcome that says the new
families were real structure rather than noise. The single largest gain was not a family at all: 73
clauses use a **compound trigger** (`When Played/On Attack:`, `When Played/When Defeated:`) that the
parser simply could not read, so those cards were being thrown away for a punctuation mark.

## Prices

Points of stats an effect costs, at the **When Played, mandatory, unconditional** baseline. `n` is
the number of clauses carrying it.

| effect | points | n | | effect | points | n |
|---|---:|---:|---|---|---:|---:|
| create X-Wing token | 3.47 | 3 | | give Weakness token | 0.99 | 2 |
| **bounce (return unit to hand)** | **2.66** | 3 | | create TIE Fighter token | 0.95 | 4 |
| **create Battle Droid token** | **2.31** | 7 | | **deal N damage to a base** | **0.78** | 7 |
| create Credit token | 2.28 | 6 | | **conditional +N/+N on itself**, per stat | **0.72** | 27 |
| **this unit enters play ready** | **2.26** | 4 | | **deal N damage to a unit (arena)** | **0.69** | 17 |
| **create Mandalorian token** | **2.22** | 7 | | **gains a keyword**, per point of list price | **0.68** | 58 |
| create Clone Trooper token | 2.19 | 4 | | deal N damage to a unit | 0.66 | 5 |
| **create Spy token** | **2.07** | 6 | | **heal N damage from your base** | **0.50** | 8 |
| peek + discard | 1.75 | 2 | | give Advantage token | 0.35 | 7 |
| ready this unit | 1.71 | 4 | | **−N/−N on an enemy**, per stat | **0.25** | 11 |
| **draw N cards** | **1.61** | 14 | | **+N/+N on another unit**, per stat | **0.24** | 16 |
| **give N Experience tokens** | **1.54** | 28 | | look at an opponent's hand | 0.13 | 3 |
| **give N Shield tokens** | **1.52** | 17 | | heal N damage from a unit | −0.15 | 2 |
| **exhaust an enemy unit** | **1.32** | 11 | | *deal N damage to a friendly unit* | *−0.24* | 13 |

Bold = `n ≥ 5` and stable under leave-one-set-out.

Two internal consistency checks land well:

- **An Experience token is +1/+1 = 2.00 weighted points, and it prices at 1.54.** The model has no
  idea what an Experience token does; it recovered roughly its body value from the stat lines of the
  cards that hand them out.
- **Dealing damage to your own unit prices negative** (−0.24 per damage, t = −2.1). The sign is right
  without being told which direction is good.

### The two new structural families

**A granted keyword costs 68% of its printed price.** `gains_keyword` is fit against each keyword's
own Phase 1 value, so the coefficient reads directly as a fraction of list. At **0.68 (t = 5.9,
n = 58)**, a *"while X, this unit gains Sentinel"* costs 0.68 × 1.55 ≈ **1.05** where printed
Sentinel costs 1.55. That corroborates the `LOF_096` Obi-Wan reading from Phase 1 (his conditional
Sentinel priced at ~61% of list) on 58 clauses instead of one card. Note the family is **100%
conditional** by construction — an unconditional grant would just be printed as the keyword.

**A stat rider is cheap, and much cheaper on someone else.** Per point of printed stat granted:

| rider | points per stat | n |
|---|---:|---:|
| conditional `+N/+N` on itself, continuous | **0.72** | 27 |
| `−N/−N` on an enemy, for the phase | **0.25** | 11 |
| `+N/+N` on another unit, for the phase | **0.24** | 16 |

A permanent-while-true buff on itself is worth roughly three times a one-phase buff handed to
another unit, and buffing a friend costs the same as debuffing an enemy — a clean symmetry the model
was not told to expect.

## Trigger and rider modifiers

Additive, per clause, relative to a mandatory unconditional **When Played**:

| modifier | points | t |
|---|---:|---:|
| **dual trigger** (`When Played/On Attack`) | **+0.63** | +2.8 |
| optional ("you may") | +0.26 | +1.8 |
| Action | +0.14 | +0.6 |
| On Attack | −0.11 | −0.7 |
| **When Defeated** | **−0.56** | −3.3 |
| **conditional** ("if …", "while …") | **−0.57** | −4.3 |

Negative = the card pays less for the same effect.

- **A compound trigger costs 0.63 more than a single one.** Firing on *either* window is worth about
  two thirds of a stat, which is the first thing measured about the 73 clauses that used to be
  unreadable.
- **When Defeated is the discount trigger** at −0.56 — delayed, and the opponent picks the moment.
- **On Attack is no longer distinguishable from When Played** (−0.11, t = −0.7). In the first pass it
  read −0.67; the dual-trigger term absorbed it, because most compound triggers are precisely
  `When Played/On Attack`. That earlier number was an artifact of the parser gap.
- **A condition refunds 0.57** — about three quarters of one point of arena damage.
- **"You may" costs 0.26 more than mandatory.** Optionality is a real premium: you can decline the
  bad half.

These are main effects, not interactions. A big On-Attack effect probably discounts by more than a
small one, but there is not enough data to fit that.

## Created tokens: a flat fee plus a sixth of the body

Replacing the seven per-token coefficients with a flat amount per token plus a slope on the token's
own printed body value now gives:

```
flat        +1.45 points per token   (t = 4.6)
body slope  +0.168                   (se 0.080, t = 2.1)
```

Under the first, narrower classifier the slope was 0.068 (t = 0.6) and the honest reading was
"flat". With the better specification the slope is **significant but tiny**: a token is priced at a
flat ~1.45 points **plus about one sixth of the body it puts on the table**. It is still nowhere
near 1.0 — you can rule out full body pricing at roughly ten standard errors — so the conclusion
holds in a softer form: **the bigger the token, the better the deal.**

| token | body | body value | fitted price |
|---|---|---:|---:|
| Battle Droid | G 1/1 | 2.00 | 2.31 |
| TIE Fighter | S 1/1 | 2.00 | 0.95 |
| Spy | G 0/2 + Raid 2 | 3.08 | 2.07 |
| X-Wing | S 2/2 | 4.00 | 3.47 |
| Clone Trooper | G 2/2 | 4.00 | 2.19 |
| Mandalorian | G 2/2 + Shielded | 5.41 | 2.22 |
| Beast | G 3/3 | 6.00 | *(no clean unit)* |

The body value counts the token's **keywords** at this fit's own coefficients — the Mandalorian's
Shielded (1.65) and the Spy's Raid 2 (1.70) are both in there. The Mandalorian is still the largest
single mispricing in the analysis: 2.22 points to put down a 2/2 that arrives with a Shield.

⚠ Each token type is printed by essentially one set (Battle Droid and Clone Trooper are TWI,
Mandalorian ASH, Spy SEC, TIE Fighter and X-Wing JTL), so `create_<token>` is nearly collinear with
set. Adding set dummies moves every coefficient by less than 0.3, so this is not a set effect
wearing a token's name — but the two sets that print **two token sizes each** still disagree about
whether size matters, and with 2–7 clauses per token that disagreement cannot be resolved here.

**The events settle it far more cleanly than the units do** — see [Phase 3](#phase-3--the-event-line-and-a-validation-of-the-whole-scale),
where a token-making event pays for its tokens at full body value, linearly, to within 0.04 points.
The residual asymmetry that survives is worth stating carefully: on an **event**, where the token is
the whole card, it is paid for in full; on a **unit**, where it rides along with a body, the stat tax
is mostly flat. That is a claim about how units are taxed for carrying text, not about what a token
is worth.

## Robustness

Leave-one-set-out on every family with enough data. Each row refits nine times, dropping one set:

| family | n | full fit | LOSO range |
|---|---:|---:|---|
| gains a keyword | 58 | 0.68 | [0.64, 0.77] |
| conditional self `+N/+N` | 27 | 0.72 | [0.69, 0.76] |
| give Experience | 28 | 1.54 | [1.49, 1.65] |
| give Shield | 17 | 1.52 | [1.31, 1.80] |
| deal damage (arena) | 17 | 0.69 | [0.60, 0.86] |
| `+N/+N` on another unit | 16 | 0.24 | [0.23, 0.27] |
| draw | 14 | 1.61 | [1.52, 1.73] |
| `−N/−N` on an enemy | 11 | 0.25 | [0.21, 0.33] |
| exhaust an enemy unit | 11 | 1.32 | [1.22, 1.44] |
| heal base | 8 | 0.50 | [0.48, 0.56] |
| deal damage to base | 7 | 0.78 | [0.73, 0.83] |

No single set drives any of the eleven, and the ranges are tighter than they were on the smaller
pool.

## What Phase 2 does *not* support

- **Events are not in this regression.** Putting them in on a cost→value curve shared with units
  gave an event cost slope of ~0 (`event × cost` = −1.14 against a unit `cost` of +1.17, t = −10.7)
  and dragged the unit terms with it — `pip_basic` fell from 0.77 to 0.32. Events get their own
  baseline in Phase 3 instead.
- **The five unpriced families above**, plus `for each` riders and self-discounts, which are
  excluded for cause rather than for lack of data.
- **Passive-downside families.** The eight cards in [Drawbacks](#drawbacks) are read individually,
  not fit; there are not enough of any one shape to make a family.
- **Any interaction** between effect size and trigger, or between effects on the same card.
- **72% of printed clauses.** The biggest remaining unpriced groups are removal (`defeat a unit
  with N or less remaining HP`), tutoring (`search the top N cards …`), discard-pile recursion, and
  the ability words (Coordinate, Disclose, Piloting).

## Flexibility keywords: Plot and Smuggle

Both let you play the card **out of your resources** and replace the resource off the top of your
deck, so neither has a hidden resource cost. They differ in exactly two ways, and those two
differences are the whole story:

| | when you may use it | what it costs to use |
|---|---|---|
| **Plot** | only when you deploy a leader | the card's **printed cost**, always |
| **Smuggle** | any time you could play the card | a separate **smuggle cost**, usually higher |

Both were originally excluded from the model. They are flexibility, not a drawback, so their
coefficients come out with the ordinary sign: the card pays stats for them.

```
Plot           +0.89 points   (se 0.43, t = 2.05, n = 4)
Smuggle        +2.07 points at a zero tax   (se 1.14, t = 1.82, n = 5)
  per +1 of smuggle cost over the printed cost:  -0.68   (se 0.76, t = -0.90)
```

Only 4 Plot and 5 Smuggle units have text the classifier can price, so these rest on nine cards.
Read them as a shape, not a table.

### Every printed smuggle cost, against the printed cost

All 15 Premier Smuggle units. The tax is +0 to +4, most often +1 or +2:

| card | | printed | pips | smuggle | pips | tax |
|---|---|---:|---|---:|---|---:|
| `SHD_204` Millennium Falcon | S | 6 | 1B1A | **6** | 1B1A | **+0** |
| `SHD_111` Collections Starhopper | S | 2 | 1B | 3 | 1B | +1 |
| `SHD_086` Warbird Stowaway | G | 3 | 1B1A | 4 | 1B1A | +1 |
| `SHD_215` Smuggler's Starfighter | S | 3 | 1B | 4 | 1B | +1 |
| `SHD_119` Weequay Pirate Gang | G | 4 | 1B | 5 | 1B | +1 |
| `SHD_089` Pirate Battle Tank | G | 6 | 1B1A | 7 | 1B1A | +1 |
| `SHD_160` Reckless Gunslinger | G | 1 | 1B | 3 | 1B | +2 |
| `SHD_097` Freetown Backup | G | 2 | 1B1A | 4 | 1B1A | +2 |
| `SHD_197` L3-37 | G | 2 | 1B1A | 4 | 1B1A | +2 |
| `SHD_148` Cassian Andor | G | 3 | 1B1A | 5 | 1B1A | +2 |
| `SHD_149` Nite Owl Skirmisher | G | 3 | 1B1A | 5 | 1B1A | +2 |
| `SHD_201` Principled Outlaw | G | 4 | 1B1A | 6 | 1B1A | +2 |
| `SHD_052` Sugi | G | 4 | **2B** | 6 | **1B** | +2 |
| `SHD_065` Vigilant Pursuit Craft | S | 5 | 1B | 7 | 1B | +2 |
| `SHD_113` Privateer Crew | G | 2 | 1B | **6** | 1B | **+4** |

**`SHD_052` Sugi is the only card whose smuggle cost changes aspects** - printed Vigilance x2, but
the smuggle cost asks for a single Vigilance. That is a genuine second axis of flexibility (a
shallower aspect penalty on the smuggled line, on top of the timing), and with one instance it
cannot be priced. Everything else keeps its printed aspects.

### What the tax buys back

Each +1 of smuggle cost refunds **0.68 points**. A full cost step on the curve is 1.4-2.1 points, so
a +1 smuggle tax gives back only about **a third to a half** of a real cost increase - which is what
you would expect, since the tax applies on one of the two lines you can play the card on. Read
backwards, it implies designers assume the smuggle line gets used roughly 35-45% of the time.

| smuggle tax | flexibility value |
|---:|---:|
| +0 | 2.07 |
| **+1** | **1.39** |
| **+2** | **0.70** |
| +3 | 0.02 |
| +4 | -0.67 |

The fitted schedule is the raw per-card mean - delta +1 averages 1.39 across three cards and
delta +2 averages 0.70 across two - so this is not a model artifact, it is the data.

**At a +4 tax the Smuggle line is worth nothing.** `SHD_113` Privateer Crew (2 cost, smuggle 6) is
the only card there, and it is the one Smuggle unit whose keyword the curve says is dead weight.

### Plot vs Smuggle: the window is worth about two resources

| | value | what you are buying |
|---|---:|---|
| Plot | **0.89** | printed cost, but only on a leader deploy |
| Smuggle at +1 | **1.39** | any time, one extra resource |
| Smuggle at +2 | **0.70** | any time, two extra resources |

**Plot sits between Smuggle +1 and Smuggle +2**, so the leader-deploy restriction costs about the
same as a two-resource tax with free timing. Unrestricted timing is worth more than a discount:
Smuggle +1 beats Plot by half a point despite charging more to use.

Per card, against a curve that prices everything else on them:

| card | | line | pays | mode |
|---|---|---|---:|---|
| `SEC_036` Dogmatic Shock Squad | G 6c | 4/6 | +2.00 | Plot |
| `SEC_084` Mas Amedda | G 4c | 3/4 | +1.04 | Plot |
| `SEC_100` Dressellian Commandos | G 5c | 4/5 | +0.68 | Plot |
| `SEC_243` FN Trooper Corps | G 5c | 4/5 | -0.15 | Plot |
| `SHD_089` Pirate Battle Tank | G 6c | 4/6 | +2.00 | Smuggle +1 |
| `SHD_119` Weequay Pirate Gang | G 4c | 3/3 | +1.36 | Smuggle +1 |
| `SHD_111` Collections Starhopper | S 2c | 2/2 | +0.80 | Smuggle +1 |
| `SHD_065` Vigilant Pursuit Craft | S 5c | 3/5 | +1.18 | Smuggle +2 |
| `SHD_149` Nite Owl Skirmisher | G 3c | 4/3 | +0.22 | Smuggle +2 |

The spread inside each mode is wide (Plot runs -0.15 to +2.00 across four cards), which is the usual
ability-side noise - the +/-1.1 residual, not the stat-side +/-0.46.

## Exploit is a cost discount, not a stat term

`Exploit N` reads *"while playing this card, defeat up to N units you control; this card costs 2
resources less for each unit defeated this way."* All 20 Premier Exploit units are TWI.

**It cannot go in the model.** Adding an `Exploit_N` term to the Phase 1 curve wrecks it: cost^2
collapses from 0.063 (t = 6.6) to 0.013 (t = 1.7), the cost slope jumps 1.32 to 1.66, and residual
sd goes 0.46 to 0.52. Exploit cards are all expensive and all heavily under-statted at their printed
cost, so a single additive term forces the model to flatten the top of the curve to accommodate
them. Exploit changes a card's **effective cost**; it is not a stat rider, and the fix is to price it
*against* the curve rather than inside it. It stays excluded from both pools.

### How much of the discount is the printed line assuming?

If a card is played with `k` exploits it effectively costs `c - 2k` resources **plus** `k` units of
fodder, so the stat line it is entitled to is

```
fair(k) = curve(c - 2k) + fodder_value x k
```

Solving `fair(k) = printed stats` gives the number of exploits the card is priced for. With fodder
valued at a **Battle Droid token (1/1 = 2.00 points)**:

| card | | c | N | printed | break-even k |
|---|---|---:|---:|---|---:|
| `TWI_167` Heavy Persuader Tank | G | 7 | 2 | 6/5 | 0.70 |
| `TWI_235` Battle Droid Legion | G | 9 | 2 | 6/5 | 0.95 |
| `TWI_087` Separatist Super Tank | G | 9 | 3 | 8/8 | 1.51 |
| `TWI_233` Hailfire Tank | G | 8 | 2 | 7/6 | 1.73 |
| `TWI_037` Droideka Security | G | 6 | 2 | 4/5 | 1.77 |
| `TWI_136` Squadron of Vultures | S | 6 | 3 | 5/4 | 1.99 |
| `TWI_117` Baktoid Spider Droid | G | 8 | 2 | 5/6 | **2.39** |
| `TWI_066` Multi-Troop Transport | G | 7 | 2 | 3/6 | **2.97** |
| `TWI_182` Infiltrating Demolisher | G | 4 | 1 | 4/5 | **2.98** |
| `TWI_118` Gor | G | 12 | 3 | 7/7 | > 4 |

**Mean break-even k = 1.89 against a mean printed Exploit N of 2.20 — a ratio of 1.05.** These cards
are statted as if you will exploit **all the way, every time**, and three of them are priced for more
exploits than they are allowed.

### It only works on 1/1 fodder

Re-run the same calculation valuing the fodder as a 2/2 body (4.00 points) instead of a 1/1:

| fodder | mean break-even k | mean k / N |
|---|---:|---:|
| Battle Droid, 1/1, 2.00 pts | **1.89** | **1.05** |
| a 2/2 body, 4.00 pts | **0.02** | **0.02** |

**With 2/2 fodder the break-even collapses to zero** — every one of these cards is only fair at *no*
exploits. Feeding an Exploit card anything bigger than a 1/1 is a straight loss on the point scale.
That is the quantitative form of "these are meant for exploiting Battle Droids": the design does not
merely tolerate token fodder, it **requires** it.

### The priciest body you can afford to feed it

Turn the question round: solve for the fodder value `F` at which a card breaks even when fully
exploited.

```
curve(c - 2N) + F x N = printed stats     ->     F = the most expensive body you can exploit
                                                    and still come out even
```

| card | | c | N | eff | printed | curve@eff | **F** | what that body is |
|---|---|---:|---:|---:|---|---:|---:|---|
| `TWI_182` Infiltrating Demolisher | G | 4 | 1 | 2 | 4/5 | 5.57 | **3.29** | a 1/1 with a keyword |
| `TWI_235` Battle Droid Legion | G | 9 | 2 | 5 | 6/5 | 4.81 | **3.16** | a 1/1 with a keyword |
| `TWI_167` Heavy Persuader Tank | G | 7 | 2 | 3 | 6/5 | 4.92 | **3.11** | a 1/1 with a keyword |
| `TWI_087` Separatist Super Tank | G | 9 | 3 | 3 | 8/8 | 7.36 | **2.88** | a 1/1 with a keyword |
| `TWI_136` Squadron of Vultures | S | 6 | 3 | 0 | 5/4 | 2.32 | **2.27** | a Battle Droid |
| `TWI_233` Hailfire Tank | G | 8 | 2 | 4 | 7/6 | 8.64 | **2.25** | a Battle Droid |
| `TWI_037` Droideka Security | G | 6 | 2 | 2 | 4/5 | 4.57 | **2.14** | a Battle Droid |
| `TWI_117` Baktoid Spider Droid | G | 8 | 2 | 4 | 5/6 | 7.36 | **1.75** | *not even a Battle Droid* |
| `TWI_066` Multi-Troop Transport | G | 7 | 2 | 3 | 3/6 | 5.53 | **1.53** | *not even a Battle Droid* |
| `TWI_118` Gor | G | 12 | 3 | 6 | 7/7 | 10.49 | **1.17** | *not even a Battle Droid* |

```
mean F = 2.36   median 2.26   range 1.17 - 3.29
Battle Droid = 2.00   Spy = 3.08   Clone Trooper = 4.00   Mandalorian = 5.41   Beast = 6.00
```

**Not one of the ten can afford a 2/2.** Zero of ten reach F = 4.00; seven of ten clear a Battle
Droid at 2.00; three do not even manage that. The whole design window is **1.2 to 3.3 points of
fodder** — which is to say a 1/1 token and nothing else. Exploiting a real unit with real stats is
never correct on this scale, and the three low-F cards are only correct if the token was going to
die anyway.

(`TWI_118` Gor's 1.17 is partly the flat-keyword problem noted below, not pure weakness — the model
over-charges him for Sentinel + Ambush + Overwhelm on a 7/7.)

### Worked example: `TWI_039` Malevolence, and when a bigger sacrifice pays

9 cost, space, 7/7, Vigilance + Villainy, **Exploit 4** (the highest in the set), Restore 2, and
*"When Played: Give an enemy unit −4/−0 for this phase. It can't attack for this phase."*

Weighted printed stats **14.00**; the curve at 9 cost with Restore 2 charged says **19.27**. So the
card pre-paid **5.27 points** for the keyword — before any exploiting, and whether or not you
exploit.

Because the curve is convex, the exploits are not worth the same amount. Each one walks two steps
down a curve that flattens as it descends, so the **first exploit is the valuable one**:

| exploit | cost step | curve there | marginal saving | fodder that step alone justifies |
|---:|---|---:|---:|---|
| 1st | 9 → 7 | 14.47 | **4.80** | a Clone Trooper 2/2 (4.00) ✓ · Mandalorian (5.41) ✗ |
| 2nd | 7 → 5 | 10.18 | **4.29** | a Clone Trooper 2/2 (4.00) ✓ |
| 3rd | 5 → 3 | 6.40 | **3.78** | a Spy 0/2 + Raid 2 (3.08) ✓ · Clone Trooper ✗ |
| 4th | 3 → 1 | 3.12 | **3.28** | a Spy (3.08) ✓ |

Total at full Exploit 4: **16.15 points** of cost saved, averaging 4.04 per exploit, and the last
exploit is worth **32% less** than the first.

**But the 5.27 it pre-paid has to come out of that.** Netting it against the total gives the real
break-even fodder price:

| exploits used | effective cost | curve there | budget for k bodies | **F per body** |
|---:|---:|---:|---:|---:|
| 1 | 7 | 14.47 | **−0.47** | **negative** |
| 2 | 5 | 10.18 | 3.82 | **1.91** |
| 3 | 3 | 6.40 | 7.60 | **2.53** |
| 4 | 1 | 3.12 | 10.88 | **2.72** |

So there are two honest answers depending on which question you are asking:

- **"Should I run it, and what can I feed it?"** — Malevolence needs **at least two exploits to be
  worth its printed line at all** (one exploit is a loss even with free fodder), and its fodder
  budget tops out at **2.72 points at full Exploit 4**. That is a Spy token, not a Clone Trooper.
  On this scale, sacrificing a real unit to Malevolence is never right.
- **"I am casting it anyway — which sacrifices are worth it?"** — with the 5.27 already sunk, the
  **first two exploits each justify a 2/2 body**, and the third and fourth only justify a 1/1 with a
  keyword. If your only fodder is Clone Troopers, exploit exactly twice.

⚠ Both figures are a **floor**: the *"−4/−0 and can't attack this phase"* clause is real value the
model does not price (stat debuffs are one of the unpriced families), so the true fodder budget is
higher than 2.72 by whatever that clause is worth.

**The general rule, across the set:** the first exploit is always the most valuable one, and its
value is set by the card's printed cost.

| card | printed cost | 1st exploit saves | last exploit saves |
|---|---:|---:|---:|
| `TWI_118` Gor | 12 | **5.42** | 4.41 |
| `TWI_039` Malevolence | 9 | 4.80 | 3.28 |
| `TWI_087` Separatist Super Tank | 9 | 4.67 | 3.65 |
| `TWI_136` Squadron of Vultures | 6 | 4.04 | 3.03 |
| `TWI_115` Osi Sobeck | 6 | 3.91 | 2.89 |

**`TWI_118` Gor's first exploit is the only one in the game that justifies a Mandalorian token**
(5.42 against the Mandalorian's 5.41), and that is purely because he is a 12-drop. Nothing cheaper
ever reaches a 2/2-plus-keyword body.

### The condition the model cannot see

Exploit's value is **conditional on your deck manufacturing 1/1s**, and that is a deckbuilding
prerequisite rather than a card-level one. With Battle Droid generation in the deck the fodder costs
2.00 and Exploit prices out as shown; without it the cheapest thing you own is a real 4-to-8-point
body and Exploit is strictly negative at every cost.

The model prices the keyword as if the 2.00-point fodder is always on the table. It is the same
blindness as the flat −0.50 refund for a "conditional" effect: the point scale sees *that* there is
a condition, never *how easily your deck meets it*. For Exploit the condition is unusually strict —
it is not "if you control a Vehicle", it is "if you are playing the Battle Droid deck".

### Why every Exploit card is expensive

The curve is convex, so 2 resources buys more points the higher up you are — but the keyword's stat
price is flat. Measured on the Phase 2 pool (where it does not corrupt the curve), Exploit costs
**1.45 points of stats per point of N** (se 0.17, t = 8.6).

| printed cost | 2 resources buys | − Battle Droid fodder | net per exploit | vs the 1.45 paid |
|---:|---:|---:|---:|---:|
| 4 | 3.27 | −2.00 | 1.27 | **−0.18** |
| 5 | 3.57 | −2.00 | 1.57 | +0.12 |
| 6 | 3.87 | −2.00 | 1.87 | +0.42 |
| 8 | 4.47 | −2.00 | 2.47 | +1.02 |
| 9 | 4.77 | −2.00 | 2.77 | +1.32 |
| 12 | 5.67 | −2.00 | 3.67 | **+2.22** |

**Exploit is break-even at 4 cost and increasingly profitable above it** — which is exactly the
printed distribution: there is no Exploit unit below 4 cost in the entire set, and the extreme case
is `TWI_118` Gor at 12.

### A limitation this exposes

`TWI_118` Gor never breaks even, even at his full Exploit 3 (effective cost 6). That is not because
he is weak — it is because the model charges him a **flat** 1.55 + 1.66 + 0.45 for Sentinel + Ambush
+ Overwhelm, and those keywords are worth far more on a 7/7 arriving on turn four than on the 2/2s
the coefficients were fit from. **Keyword prices in this model do not scale with the body they sit
on**, and Exploit — whose whole purpose is to put a large body down early — is where that bites
hardest.

## Drawbacks

Yes, but only partly, and the coverage is uneven. Three things are true:

**Priced.** `Bounty` is a keyword drawback and is now a Phase 1 term. `deal N damage to a friendly
unit` is a Phase 2 family (−0.23 per damage, t = −1.9) — the model works out the sign on its own.

**Excluded.** Units carrying `Piloting` or `Support` are still dropped from both pools. `Exploit` is
excluded too, but for a different reason and it *is* measured - see
[Exploit](#exploit-is-a-cost-discount-not-a-stat-term). `Plot` and `Smuggle` used to be excluded and
are now priced - see [Flexibility keywords](#flexibility-keywords-plot-and-smuggle).

**Unpriced.** Passive downsides written as ability text — *"this unit can't attack"*, *"an opponent
creates a token"*, *"this unit doesn't ready"* — have no family. There are few enough of them to
read individually, and they are below.

### Bounty costs the card 0.75 points, and it pays

`Bounty` was originally excluded as a "value-bearing keyword" alongside Smuggle and Exploit. That was
wrong — the value goes to the **opponent**, so it is a drawback and the card is handed extra stats to
carry it. All seven Premier Bounty units are otherwise keyword-only, so they fit cleanly:

```
Bounty = +0.75 points   (se 0.19, t = 3.96, n = 7)
```

Every one of the seven is over-statted, and tightly:

| card | | line | over curve | bounty reward (the opponent collects) |
|---|---|---|---:|---|
| `SHD_027` Hylobon Enforcer | G 1c | 1/4 | +1.18 | Draw a card |
| `SHD_195` Cartel Turncoat | S 1c | 2/3 | +0.93 | Draw a card |
| `SHD_095` Clone Deserter | G 1c | 2/3 | +0.91 | Draw a card |
| `SHD_134` Guavian Antagonizer | G 1c | 2/3 | +0.67 | Draw a card |
| `SHD_167` Wanted Insurgents | G 3c | 4/4 | +0.64 | Deal 2 damage to a unit |
| `SHD_185` Doctor Evazan | G 2c | 3/3 | +0.63 | Ready up to 12 resources |
| `SHD_211` Fugitive Wookiee | G 2c | 3/3 | +0.28 | Exhaust a unit |
| | | | **mean +0.75, sd 0.28** | |

The four "Draw a card" bounties average **+0.95**, and Phase 2 prices a drawn card at **1.85**. So a
bounty is worth **about half the reward's face value** — which is what you would expect from a
drawback that only fires if the unit dies or is captured, and only on the opponent's terms. Adding
Bounty as a term moved every other Phase 1 coefficient by less than 0.04.

### Passive downsides, read one at a time

Too few for a family, so these are individual readings against the curve. Positive = the card was
given extra stats to carry the drawback.

| card | | line | over curve | drawback |
|---|---|---|---:|---|
| `JTL_059` Corporate Defense Shuttle | S 2c | 3/5 | **+2.76** | This unit can't attack |
| `LOF_063` Oggdo Bogdo | G 3c | 5/5 | +1.87 | Can't attack unless damaged (plus an upside clause) |
| `HMW_152` Babwa Venomor | G 2c | 4/4 | +1.43 | When Played: **an opponent** creates a Beast token |
| `LOF_044` Loth-Wolf | G 2c | 3/3 | +1.26 | This unit can't attack |
| `SHD_161` Stolen Landspeeder | G 1c | 3/2 | +0.93 | If played from hand, an opponent takes control of it |
| `ASH_034` Wicket | G 1c | 3/3 | +0.25 | This unit can't attack bases |
| `TWI_145` Jesse | G 3c | 4/4 | +0.19 | When Played: **an opponent** creates 2 Battle Droid tokens |
| `JTL_182` Rampart | S 2c | 3/3 | −0.27 | Doesn't ready in regroup unless its power is 4+ |

**"Can't attack" is the expensive one** — +2.76 and +1.26 on the two clean cases, so roughly 1–3
points depending on how much of the body was offence to begin with (`JTL_059` is a 3/5 whose power
is entirely wasted; `LOF_044` is a 3/3). `ASH_034`'s "can't attack **bases**" is nearly free at
+0.25, which is the right shape — you keep the trading.

**Giving the opponent a token refunds far less than making one costs you.** Phase 2 prices creating
a token at ~2.0–2.2 points; handing the opponent a Beast (a 3/3, 6.00 points of body) refunds
**1.43**, and handing them two Battle Droids (4.00 points) refunds **0.19**. Either designers rate a
body on the opponent's board well below the same body on yours — plausible, since it arrives off-plan
and on your timing — or these two cards are simply weak. Two data points cannot separate those.

## Robustness

Leave-one-set-out on the families with enough data. Each row refits nine times, dropping one set:

| family | full fit | LOSO range |
|---|---:|---|
| give Experience | 1.82 | [1.71, 2.07] |
| draw | 1.85 | [1.72, 1.98] |
| deal damage (arena) | 0.87 | [0.77, 0.97] |
| deal damage to base | 0.96 | [0.86, 1.03] |
| give Shield | 1.60 | [1.38, 1.82] |
| heal base | 0.51 | [0.48, 0.54] |

No single set drives any of the six.

## What Phase 2 does *not* support

- **Events are not in this regression.** Putting them in on a cost→value curve shared with units
  gave an event cost slope of ~0 (`event × cost` = −1.14 against a unit `cost` of +1.17, t = −10.7)
  and dragged the unit terms with it — `pip_basic` fell from 0.77 to 0.32. Events need their own
  baseline; Phase 3 below derives one without a regression.
- **The five unpriced families above.**
- **Passive-downside families.** The eight cards in [Drawbacks](#drawbacks) are read individually,
  not fit; there are not enough of any one shape to make a family.
- **Any interaction** between effect size and trigger, or between effects on the same card.

## Does rarity skew any of this? Mostly no.

**Phase 1 has no rarity exposure to skew.** The keyword-only pool is 253 Common, 13 Uncommon,
5 Rare, 1 Special and **1 Legendary**. A card at the top of the rarity ladder essentially always has
text, so it is never in the stat pool. The stat curve is a Common-and-Uncommon curve by construction,
and cannot be dragged by pushed rares.

**Phase 2 contains zero Legendaries.** The 146 classified ability units are 87 Common, 38 Uncommon,
13 Rare and 8 Special — no Legendary's text is simple enough to classify. What *is* there is real:
adding rarity dummies to the effect fit gives **Uncommon +0.77 (t = 3.3)** and **Rare +1.26
(t = 3.4)** points of extra budget, and moves R² 0.9213 → 0.9260.

But controlling for it barely touches the prices:

| family | no control | + rarity | shift |
|---|---:|---:|---:|
| give Experience | 1.82 | 1.94 | +0.12 |
| deal damage (arena) | 0.87 | 1.00 | +0.13 |
| draw | 1.85 | 1.90 | +0.04 |
| give Shield | 1.60 | 1.66 | +0.06 |
| deal damage to base | 0.96 | 0.99 | +0.03 |
| heal base | 0.51 | 0.49 | −0.02 |
| every create-token family | — | — | ≤ 0.07 |

Only `peek + discard` (+0.70) and `bounce` (+0.61) move materially, and both have n ≤ 3. **The
headline effect prices are robust to rarity; read them as very slightly conservative.**

### The Legendary premium is a cost-mix artifact

Measuring the raw **text budget** — how many points of stats a card gave up to have printed text,
`curve − printed stats` — over all 1,131 Premier ability units gives a clean-looking ladder:

| rarity | n | mean | median |
|---|---:|---:|---:|
| Common | 323 | 1.55 | 1.37 |
| Uncommon | 344 | 1.93 | 1.68 |
| Rare | 278 | 2.20 | 2.09 |
| **Legendary** | **129** | **2.61** | **2.25** |
| Special | 57 | 1.90 | 1.68 |

It does not survive controlling for cost. Legendaries are printed expensive — 4 of them at 2 cost
against 79 at 6+ — and text budget rises with cost for everybody:

| rarity | 2–3 cost | | 6+ cost | |
|---|---:|---:|---:|---:|
| | n | mean | n | mean |
| Common | 170 | 1.36 | 33 | 2.90 |
| Uncommon | 154 | 1.56 | 66 | 3.04 |
| Rare | 105 | 1.82 | 68 | 3.00 |
| **Legendary** | **17** | **1.10** | **79** | **3.07** |

At 6+ cost every rarity converges on ~3.0. In the cheap band Legendaries spend **less** than any
other rarity. Regressing text budget on cost (quadratic) plus rarity dummies over all 1,131 units:

| | coef | t |
|---|---:|---:|
| cost | +0.247 | +3.0 |
| **Rare** | **+0.376** | **+3.0** |
| Special | +0.309 | +1.4 |
| Uncommon | +0.213 | +1.8 |
| **Legendary** | **+0.148** | **+0.8** |

**Legendary is not the pushed rarity — Rare is, and only by about 0.4 points.** Once cost is
controlled, a Legendary gets no more text budget than a Common.

### A select few Legendaries

The aggregate says no rarity rule, so the interesting variation is *within* Legendaries, and it is
large. Cheap Legendaries get their text close to free; expensive ones pay near list price.

| card | | line | budget spent | what it buys |
|---|---|---|---:|---|
| `LOF_132` Grand Inquisitor | 3c | 3/4 | **0.30** | Hidden + Raid 1 already priced in the curve; *other friendly Inquisitors gain Hidden* is the 0.30 |
| `TWI_034` General Grievous | 3c | 4/4 | 0.68 | ignore the aspect penalty on Lightsaber upgrades |
| `LOF_246` Grogu, Mysterious Child | 3c | 1/6 | 0.93 | Hidden, plus a repeatable Action: move up to 2 damage between units |
| `SOR_179` Boba Fett | 3c | 3/5 | 0.96 | On Attack, conditional: 3 damage to the defender |
| `ASH_155` Grogu, Yes. Yes. Yes. | 3c | 2/6 | 1.23 | a free attack **every round** on taking the initiative |
| `LOF_130` HK-47 | 2c | 2/4 | **1.31** | **unlimited repeatable** 1 damage to a base whenever an enemy unit dies |
| `SEC_147` Chopper | 2c | 4/1 | 1.62 | symmetric hand discard on combat damage to a base |
| `SEC_042` Cassian Andor | 2c | 2/2 | 3.04 | attacker −2/−0 while defending **and** prevent 2 from enemy abilities |
| `SEC_101` Queen Amidala | 5c | 5/3 | 4.07 | 2 Spy tokens **and** a sacrifice-to-prevent clause |
| `LAW_074` Maz Kanata | 5c | 4/4 | 4.61 | repeating tutor-and-play at −4 cost |
| `HMW_110` Emperor Palpatine | 5c | 3/2 | 7.21 | take control of an enemy unit costing ≤3, plus 2 Weakness |
| `LAW_063` L3-37 | 6c | 3/2 | **8.83** | search the top 10, play any number of Droids totalling ≤5 cost, free |

Two readings worth pulling out:

**`LOF_130` HK-47 is the sharpest underpricing in the sample.** A single instance of "deal 1 damage
to a base" prices at **0.96** in Phase 2. HK-47 pays **1.31** for an unbounded, repeatable version of
it — 1.4× the price of one use, for arbitrarily many. `ASH_155` Grogu is the same shape: 1.23 for a
free attack every round forever.

**The one matched pair in the pool goes the Legendary's way.** `SEC_101` Queen Amidala (Legendary)
and `SEC_191` Trade Federation Delegates (Common) are both 5-cost ground, both 2-pip, and both read
*"When Played: Create 2 Spy tokens."*

| | rarity | line | weighted | curve | spent | extra text |
|---|---|---|---:|---:|---:|---|
| `SEC_191` Delegates | Common | 3/4 | 6.86 | 11.56 | **4.70** | none |
| `SEC_101` Amidala | Legendary | 5/3 | 8.28 | 12.35 | **4.07** | sacrifice a trait-sharing friendly to prevent damage |

The Legendary has the bigger body, the same token clause, an extra defensive clause, and pays
**0.63 less**. That is worth roughly two points of premium on this card — but it is one card, and
the 1,131-unit regression above says it is not a rule.


---

# Phase 3 — the event line, and a validation of the whole scale

Events carry no stat line, so they cannot be regressed alongside units. But a handful of events do
**nothing but hand over bodies**, and those price themselves: whatever the event costs buys exactly
the token bodies it creates. That gives an independent read on the point scale, derived from a
completely different card type.

Six such events, with each token valued at its own printed body:

| card | | aspects | delivers | body pts | per cost |
|---|---|---|---|---:|---:|
| `TWI_237` Droid Deployment | 2c | Villainy | 2× Battle Droid | 4.00 | 2.00 |
| `TWI_251` Drop In | 4c | Heroism | 2× Clone Trooper | 8.00 | 2.00 |
| `JTL_254` Dedicated Wingmen | 4c | Heroism | 2× X-Wing | 8.00 | 2.00 |
| `HMW_272` Growth | 5c | *(colorless)* | 1× Beast + heal 3 base + draw 1 | 9.38 | 1.88 |
| `ASH_140` Stronger Together | 4c | Command | 2× Mandalorian | 10.82 | 2.71 |
| `SEC_092` I Am the Senate | 6c | Command, Villainy | 5× Spy | 15.40 | 2.57 |

## The line

Subtracting the Phase 1 aspect values (basic +0.77, alignment +0.52) to normalise every card to a
0-pip event, the first four fall on one line:

```
0-pip event value = 1.97 × cost − 0.45          residuals: −0.02, +0.03, +0.03, −0.04
```

**Four cards, four different token types, three different sets, and one card that also heals and
draws — all within 0.04 points of a single line.** This is the strongest result in the document,
and it validates four things at once that were derived entirely from unit stat lines:

1. **The point scale itself.** A Beast token is 6.00 points because 3/3 is 6.00 points, and `HMW_272`
   lands on the line.
2. **`heal N from your base` = 0.51/damage and `draw N` = 1.85/card.** Both were fit from units.
   Plugging them into `HMW_272` — an event, in a preview set — puts it on the line to 0.04.
3. **The aspect values.** `HMW_272` is colorless and the other three carry pips; they only agree
   after subtracting exactly the Phase 1 pip values. If those were wrong, the line would not close.
4. **Tokens on events are priced at full body, linearly.** Battle Droid 2.00, Clone Trooper 4.00,
   X-Wing 4.00, Beast 6.00 — and `TWI_237` charges 1 cost per Battle Droid while `TWI_251` charges
   2 cost per Clone Trooper, exactly double for exactly double the body.

**Events run about 1 point behind units of the same cost.** The 0-pip event line gives 3.49 at cost
2, 7.43 at cost 4, 11.37 at cost 6, against a 0-pip ground unit's 4.95 / 8.37 / 12.29 — a gap of
0.9–1.5 points. That is the price of the card leaving after it resolves, and it is the missing
constant the failed joint regression was trying to estimate.

## The two that miss, and the open question

`ASH_140` is **+2.60** over the line and `SEC_092` is **+2.71** over. They are the only two cards
here whose token carries a keyword, which is suggestive — but the two do not admit one explanation:

| card | budget | ÷ tokens | token stat line | implied keyword value | vs unit price |
|---|---:|---:|---|---:|---|
| `ASH_140` | 8.20 | 4.10 each | Mandalorian 2/2 = 4.00 | Shielded ≈ **0.10** | 1.41–1.66 on a unit |
| `SEC_092` | 12.66 | 2.53 each | Spy 0/2 = 1.72 | Raid 2 ≈ **0.81** | 1.36–1.70 on a unit |

If token keywords were simply free, `ASH_140` would land on the line (delivering 8.00 against a
8.20 budget) but `SEC_092` would fall **4.06 points under** it. So Spy's Raid 2 is worth real
points and Mandalorian's Shielded apparently is not, which is not a rule — it is two data points
disagreeing.

The alternatives are that these two cards are pushed, or that a token's keyword is valued by
something my accounting misses. `SEC_092` is rarity **Special** — a showcase card, where being
pushed is expected — which covers one of the two. `ASH_140` is a Common, and Mandalorian tokens are
ASH's marquee mechanic. **Token keyword valuation is unresolved and is the cleanest open question
in the analysis**; `SEC_246` Contempt for Culture (2c Villainy: 2 damage to a non-Vehicle unit, then
create a Spy) is the obvious third data point, and it needs a `non-Vehicle unit` damage pattern the
classifier does not yet have.

## Why this does not become a general event curve yet

The six cards above work because their entire content is bodies, which the point scale already
measures. Broadening to all events means pricing effects that have no unit-side anchor, and the
attempt to do it by regression on the 24 token-free classified events produced
`value = 1.34 + 0.55 × cost` with a residual sd of 1.12 — a slope less than a third of the token
line's. That is not a measurement of events; it is a measurement of how much of an event's text the
classifier can currently see. Widen the classifier first.

---

## Open phases

1. **Generalise the event line.** Phase 3 pins `value = 1.97 × cost − 0.45` from six events whose
   whole content is bodies. Extending it to events that do other things needs effect prices with a
   unit-side anchor, which loops back to item 2. `HMW_151` Overgrowth (5c: *if you control a
   Kashyyyk base*, a friendly unit deals damage equal to its power to an enemy unit — then resource
   this card) vs `SOR_126` Resupply (3c, put this event into play as a resource) is the natural next
   pair: against the event line it isolates the power-strike clause, conditional, at
   9.40 − 5.46 = **3.94 points** given a "resource a card" baseline — but that baseline is itself
   unmeasured, so it needs a third card.
2. **Token keyword valuation** — the cleanest open question. `ASH_140` and `SEC_092` are the only
   two token events that miss the line, they are the only two whose token has a keyword, and they
   disagree about what that keyword is worth (Shielded ≈ 0.10, Raid 2 ≈ 0.81). `SEC_246` Contempt
   for Culture is the third data point and needs a `non-Vehicle unit` damage pattern in the
   classifier.
3. **Upgrades** have stats and cost and can get a Phase-1-style curve of their own. Untouched.
4. **Widen the classifier.** 13% coverage is the binding constraint on every Phase 2 number. The
   biggest unpriced groups are stat buffs/debuffs (`give a unit −N/−N for this phase` alone is 9
   clauses), mass damage (`deal N damage to each enemy ground unit`), and discard-pile recursion.
   Each is a family in its own right.
5. **Capture and Strike True have zero clean units.** Pricing them needs either multi-clause
   decomposition (fit a card with two known clauses as the sum) or hand-picked comparison pairs.
6. **Multi-clause decomposition** would roughly triple the usable sample: 238 Premier units have
   exactly two clauses, and a card with one known and one unknown clause is currently discarded
   whole.
7. **Rarity is settled for now** — see the rarity section. Rare gets +0.38 points of text budget
   once cost is controlled; Legendary gets +0.15 (t = 0.8, i.e. nothing). The open piece is
   *within*-Legendary variation: cheap Legendaries spend 1.10 on text against 3.07 for 6+ cost ones,
   and the cheap end is where the repeatable-trigger underpricing lives (`LOF_130`, `ASH_155`).
   Pricing repeatable vs one-shot triggers is the family that would capture it.
8. **Leaders and bases** are untouched.
