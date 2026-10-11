# Bot card-tag weights

The source of truth for every card tag the bot's tagger emits (`SWUSim/DevTools/rl/tag_cards.php`) and the weight the
fallback scorer gives it in each deck style. **Every tag is listed**, weighted or not; `0.00` means the tag is
recorded but earns nothing in the flat play value yet.

The code reads the same numbers from `SWUBotWeightTable()` in `SWUSim/Custom/BotArchetypes.php`, and
`SWUSim/DevTools/tests/bot_tagweights_test.php` fails if this table and the code ever disagree, or if a tag is
missing here — **change both together**. The weights are the printed values; `SWUBotWeights()` derives the live ones
(racing shifts a seat's column toward aggro, and probes or levers can scale a weight).

| tag | hyperaggro | softaggro | midrange | softcontrol | hardcontrol |
|---|---|---|---|---|---|
| `ambush` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `attack-no-base` | -0.40 | -0.30 | -0.10 | 0.00 | 0.00 |
| `bounce` | 0.25 | 0.30 | 0.50 | 0.55 | 0.60 |
| `bounty` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-capture` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-damage-enemy-unit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-draw` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-exhaust-enemy-unit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-gives-experience` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-recursion` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-removal` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `bounty-resource-ramp` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `buff` | 0.55 | 0.50 | 0.40 | 0.35 | 0.30 |
| `capture` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `coordinate` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-bounce` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-damage-friendly-base` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-damage-friendly-unit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-defeat-friendly-resource` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-defeat-upgrade` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-exhaust-friendly-unit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-mill-self` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-removal` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-sacrifice` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-self-burn` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-self-damage` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `cost-uses-the-force` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-battle-droid-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-beast-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-clone-trooper-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-credit-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-mandalorian-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-spy-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-tie-fighter-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `create-x-wing-token` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `credit-ramp` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `damage-all-units-spread` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `damage-enemy-base` | 0.70 | 0.80 | 0.40 | 0.30 | 0.30 |
| `damage-enemy-unit` | 0.35 | 0.45 | 0.60 | 0.70 | 0.80 |
| `damage-enemy-unit-spread` | 0.10 | 0.15 | 0.25 | 0.35 | 0.40 |
| `damage-friendly-base` | -0.30 | -0.40 | -0.50 | -0.55 | -0.60 |
| `damage-friendly-unit` | -0.20 | -0.25 | -0.30 | -0.35 | -0.40 |
| `damage-wipe-enemy` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `damage-wipe-friendly` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `debuff` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `debuff-all-enemy-units` | 0.35 | 0.45 | 0.60 | 0.70 | 0.80 |
| `defeat-friendly-resource` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `defeat-shield` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `defeat-upgrade` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `defeat-wipe-enemy` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `defeat-wipe-friendly` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `discount` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `draw` | 0.20 | 0.30 | 0.50 | 1.00 | 1.40 |
| `exhaust` | 0.25 | 0.30 | 0.40 | 0.40 | 0.40 |
| `exhaust-enemy-resource` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `exhaust-enemy-unit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `exhaust-friendly-unit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `exploit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `fortify` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gains-the-force` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-advantage` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-ambush` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-experience` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-grit` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-hidden` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-overwhelm` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-raid` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-restore` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-saboteur` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-sentinel` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-shield` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-shielded` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `gives-weakness` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `grants-attack` | 0.70 | 0.60 | 0.40 | 0.30 | 0.20 |
| `grit` | 0.30 | 0.30 | 0.30 | 0.30 | 0.30 |
| `heal` | 0.05 | 0.10 | 0.30 | 0.50 | 0.80 |
| `heal-friendly-base-spread` | 0.05 | 0.10 | 0.30 | 0.50 | 0.80 |
| `heal-friendly-units-spread` | 0.05 | 0.10 | 0.30 | 0.50 | 0.80 |
| `heal-on-enemy-defeat` | 0.30 | 0.40 | 0.60 | 0.80 | 1.00 |
| `hidden` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `indirect-damage` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `mill-opponent` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `mill-self` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `overwhelm` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `piloting` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `plot` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `power-strike` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `pump` | 0.15 | 0.10 | 0.05 | 0.05 | 0.00 |
| `raid` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `recursion` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `removal` | 0.40 | 0.50 | 0.80 | 1.10 | 1.40 |
| `resource-ramp` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `restore` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `saboteur` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `sacrifice` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `search-top-deck` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `self-burn` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `self-damage` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `sentinel` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `shielded` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `shoot-first` | 0.20 | 0.20 | 0.25 | 0.25 | 0.25 |
| `smuggle` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `steal-enemy-resource` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `take-control` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `uses-the-force` | 0.00 | 0.00 | 0.00 | 0.00 | 0.00 |
| `wipe` | 0.00 | 0.10 | 0.40 | 1.00 | 1.80 |

## Notes

- **Board-scaled, never flat** (`_SWUBotPlayValue`, `SWUSim/Custom/BotFallback.php`):
  - `debuff-all-enemy-units` — the weight is paid **once per enemy unit the -X/-X would kill** (Lawbringer: the best
    single aspect). Zero against a board of big units.
  - `power-strike` — its row is `0.00` on purpose. With **no striker** (no friendly unit, and the card is not its own
    striker) the card's `damage-enemy-unit` value is taken back; when the striker's power can **kill** an enemy unit,
    the style's `kill` weight is added.
  - `heal-on-enemy-defeat` — the weight is paid **per point of life healed** by a "When an enemy unit is defeated:
    Heal N" engine (Chimaera 2, Iden Versio 1): playing the engine earns N × the kills it can expect (its own When
    Played kill plus the removal and sweeps already in hand); while it is in play, every other kill earns N more
    (Lost and Forgotten heals 5, not 3). Healing is capped at the base's damage plus 4 — life past that is wasted.
- **Increments, not full weights.** `power-strike` and both spread tags sit on top of `damage-enemy-unit`, which every
  one of those cards also carries; `pump` sits on top of `grants-attack` (and, on 41 of 62 cards, `buff`).
- **Drawbacks are negative.** `damage-friendly-unit`, `damage-friendly-base` and `attack-no-base` cost a card value.
- **Prefixed tags are never weighted.** `cost-…` (what a card COSTS, e.g. a Smuggle cost) and `bounty-…` (what the
  OPPONENT collects) describe someone else's side of the card.
- **Retired 2026-10-01:** `damage` → `damage-enemy-unit`, `burn` → `damage-enemy-base` (same weights). Neither is
  emitted any more.
- `grit` is also a combat weight key, so a card with the Grit keyword earns it in the flat sum.
