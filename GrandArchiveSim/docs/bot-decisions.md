# Optional bot decisions

`BotLegalActions.php` offers every live single-card choice, including encoded opportunity
abilities, plus PASS for optional choices. YESNO offers both answers. Multichoice retains
its full candidate pool and bounds in the decision context; the strategy builds a bounded
subset instead of enumerating exponentially many combinations.

`BotDecisions.php` scores known choices with the acting seat's perspective and restores the
caller's perspective afterward. Policies currently cover:

- Affordable champion progression and ordinary materialization, preferring guns over ammunition.
- Graveyard floating-memory payments, capped at the amount owed, preserving field permanents.
- Starter bullet loading and optional weapon use without extra reserve taxes.
- Evasive Maneuvers when two prevention changes lethal incoming combat damage into survival,
  with a matching friendly-target policy and guards against duplicate spending.
- Umbra Sight's optional memory draw, accounting for curse damage and a life/deck margin.
- Free Recover 2/3/5 prompts, floating-memory-to-draw, and Diana's Aethercharge load sequence.
- Genbu Guo Jia: take level-up quest counters while an active Sapphire Fatestone needs them;
  prefer level-three recovery at four or fewer remaining life; transform Sapphire when legal.
- Bovine/Primordial Ritual self-mill while Sapphire is active, deluge is below twelve, and
  more than eight cards remain in the bot's deck.
- Frostsworn Paladin's floating-memory banish for draw and buff, preserving the last deck card
  and preferring non-water floating cards to preserve deluge.

This is a conservative heuristic, not a complete combat simulator. Other fast actions,
unknown optional sacrifices/discards and special materialization costs still pass. Unsupported
optional policies are reported through `GABotUnsupportedOptionalDecisions` and the server log,
including source card IDs for unsupported single-card choices. Add explicit handler/card rules
and tests for new effects; do not accept an effect merely because its tooltip contains “draw”.
Required choices retain deterministic fallbacks; reserve payment and attack targeting retain
existing heuristics. No shared engine, generated code, or player rules are changed.

Run focused real-runtime checks:

```sh
php -d xdebug.mode=off DevTools/GABotDecisionTest.php
```

Run starter self-play (APCu enabled, or the harness's CLI fallback):

```sh
php -d apc.enable_cli=1 -d xdebug.mode=off DevTools/GABotSelfPlayTest.php --deck=GrandArchiveSim/Tests/BotFixtures/diana-starter.txt --deck2=GrandArchiveSim/Tests/BotFixtures/diana-starter.txt
```

## Genbu validation (2026-10-28)

The focused runtime tests cover both seats, viewer-perspective restoration, actual quest and
transformation effects, insufficient counters/rested Sapphire, recovery at critical life,
self-mill limits, and Paladin's actual banish/draw/buff resolution.

A local six-game shuffled Genbu mirror comparison alternated the updated policy's seat against
`83d802dbf`'s decision policy, with the same legal-action enumeration and free-play chooser.
The updated policy won 6/6; all games completed with the self-play harness's ten checks passing.
This is a small exploratory sample, not a general win-rate estimate. Updated chooser means were
0.28–0.74 ms per game, with per-game p95 values 1.28–4.67 ms. These measure chooser time only,
not engine execution or browser polling. The experiment used the Guo Jia prefill `1xg75xy`.

This increment does not add lookahead, a trained model, or full archetype coverage. Unknown
fast responses and most spell-target decisions still need dedicated policies.

## Combat targeting follow-up (2026-10-28)

Attack-target selection now uses the engine's attack threat amount (including intent power)
when estimating lethal damage. It weighs removing allies against champion pressure, penalizes
losing the attacker to a ready or Steadfast defender, and strongly avoids exposing its own
champion to lethal retaliation. Only offered targets are considered; hidden cards are not read.
This remains a public-board heuristic: prevention, Ambush and card-specific retaliation
restrictions are not fully simulated, and free-play attack ordering is unchanged.

The decision suite passes 67 checks, including both seats choosing lethal, taking a safe ally
kill, avoiding an unnecessary trade, recognizing a rested defender, avoiding ineffective chip
damage, and restoring viewer perspective. A shuffled Genbu mirror completed in 353 bot steps
with all ten self-play checks passing. This is completion validation, not a strength benchmark.

### Damage target follow-up (2026-10-28)

Mote Sear, Voltaic Sphere, and Thunderclap now rank offered targets using explicit
card damage amounts, both during `GADeclareTarget` primary selection and legacy
card continuations. Prefer champion lethal, then profitable ally removal; avoid
friendly fire when an opposing target is offered. Voltaic Sphere uses five damage
against allies. Mote Sear counts only its guaranteed base two damage. Unknown cards
and other target keys retain the existing fallback.

This changes target selection, not when the bot activates these spells. Scoring
uses public HP, power, and controller information; it does not yet simulate damage
prevention or replacement effects. The existing Evasive Maneuvers policy remains.

Validation: 91 decision checks passed, covering both seats, lethal, friendly fire,
card-specific damage thresholds, unknown-card fallback, and perspective restoration.
A randomized Genbu mirror completed in 391 steps with all 10 harness checks passing;
that is a regression smoke test, not a spell coverage or strength benchmark.

### Recovery response follow-up (2026-10-28)

The default heuristic now activates Extinguishing Synchron when its full effective
recovery can be used, or when healing lets its champion survive the current lethal
attack. It holds the item at full health, when recovery is blocked, and when healing
alone cannot avert an already-lethal attack. This remains a one-action estimate;
it does not plan combinations of multiple defensive responses.

The read-only recovery estimate mirrors RecoverChampion's current CANT_RECOVER,
Morgan, Serpentine Judicator, Infernal Vessel, and Transfusive Aura rules. Keep this
estimate in sync if those engine rules change. Actual costs and activation legality
remain controlled by the engine. No hidden opponent information is read.

Validation: 111 decision checks pass, including both seats, refinement, blocked and
reduced recovery, lethal combat, and actual recovery effect resolution. A randomized
Genbu mirror also passed all 10 completion checks. This does not measure win-rate.

### Hand-play sequencing follow-up (2026-10-28)

The default heuristic now scores Mote Sear, Voltaic Sphere, and Thunderclap against
actual primary declaration targets before selecting a hand play. Estimated lethal
spell damage takes priority; estimated ally removal precedes routine pressure
attacks, while the existing potential-lethal attack shortcut remains. Friendly-only
and negative-value damage plays are held. Evasive Maneuvers is reserved for its
existing response policy rather than spent during free play.

Scores are computed once per hand candidate, with seat perspective restored in a
finally block. No opposing hidden zones are read. Unrecognized cards retain the
cost-based fallback. These estimates share the damage target policy's limitations:
no simulation of prevention/replacement effects, optional Mote Sear bonuses, or
multi-action plans. This is incremental sequencing support, not general synergy
planning or a measured win-rate increase.

Validation: 127 real-runtime decision checks pass, including both seats, removal
before attacks, lethal spells, friendly-only targets, prevention retention, and
perspective restoration. A shuffled Genbu mirror completed with all 10 harness
checks passing; that is a completion regression check, not strength evidence.

### Retaliation targeting follow-up (2026-10-28)

Combat target scoring now uses the engine's retaliation eligibility rules, including
off-target Ambush, rested-unit restrictions, and attacks that cannot be retaliated.
It estimates the strongest eligible single retaliator instead of only inspecting
the target. GetRetaliatorOptions accepts an optional candidate target for read-only
previews; existing callers retain their current behavior and combat state is unchanged.

Validation: 139 decision checks passed, including both seats for Ambush, resting,
Blazing Bowman, and preservation of the committed target. A shuffled Genbu mirror
completed in 384 bot steps with all 10 self-play checks passing (40 combat events).
This confirms completion, not a measured win-rate increase. Prevention, response
cards, and special retaliation damage modifiers remain outside this estimate.

### Optional Ring draw follow-up (2026-10-28)

The default heuristic now uses Grand Crusader's Ring's draw ability when activation
is legal and at least two cards remain in its deck. It holds the Ring under the
engine's no-draw effect, Tithe Proclamation's draw cap, or Mandate of Honor's
influence restriction. Recovery and urgent defensive responses retain higher
scores. The policy reads public board state and its own zone counts only.

Validation: 161 real-runtime decision checks pass, including both seats, draw
restrictions, perspective restoration, recovery priority, and the actual Ring
banish-and-draw effect. A shuffled Genbu mirror completed in 369 steps with all
10 harness checks passing. These are correctness checks, not win-rate evidence.
Draw-trigger penalties and multi-action combo planning remain outside this policy.

### Genbu defense follow-up (2026-10-28)

Genbu, Black Tortoise now uses its temporary life boost in response to a lethal
attack targeting itself when the boost would let it survive. The heuristic uses
the engine's water-graveyard count and activation prerequisite, including the
three-reserve requirement and once-per-turn restriction. It declines outside
combat, after damage, against a different target, or when the boost is unnecessary
or insufficient. This covers incoming attacks, not retaliation or spell damage;
prevention and multi-response combinations remain outside the estimate.

Validation: 183 real-runtime checks pass, with both seats covered and the actual
life-boost effect verified. A shuffled Genbu mirror completed in 334 steps with
all 10 harness checks passing (38 combat events). This validates completion, not
a measured increase in win rate.

### Repeatable policy evaluation (2026-10-28)

The heuristic comparison harness is implemented; see [bot-evaluation.md](bot-evaluation.md) for commands and interpretation. It freezes chooser helpers, runs seeded seat-swapped matches under the current engine, and reports optional ability coverage plus matchup outcomes and decision timing. The initial four-deck configuration schedules 160 games. The frozen snapshot includes today's improvements, so this initial run establishes a baseline rather than claiming increased strength. RL models are not involved.

### Deck knowledge and hidden-information boundary (2026-10-28)

Added [deck knowledge](bot-knowledge.md): submitted card counts minus known cards, without-replacement draw odds, uncertainty bounds, probability-aware Ring draw valuation, and active glimpse ordering. Removed the competitive bot's use of the goldfish card-naming fallback, which could inspect the top of the deck. New tests cover both seats and throw on hidden identity reads. Existing frozen baselines remain unchanged; newly frozen policies include `BotKnowledge.php`.

### Orb, Enlighten, and Polar Depths coverage

- Enfeebling Orb: deny two opposing hand cards during our main phase. Preserve it
  against smaller hands and before recollection; inspect opponent hand size only.
  When choosing our own cards for an opposing Orb, put inaccessible elements and
  then higher reserve-cost cards into memory first.
- Diana, Duskstalker's opportunity gaps include inherited **Lineage Release:
  Materialize Gun** and dynamic **Enlighten**. Release Keen Huntress only when
  an affordable gun is available and no gun is already on our field. Identify the current dynamic slot
  by its engine descriptor so all champions can use it. Spend counters for a useful
  draw, respecting existing draw restrictions and the last-card safety reserve.
  This does not yet price alternative uses of enlighten counters.
- Fractal of Polar Depths: sacrifice to empty the opponent's deck, or safely advance
  an active Sapphire/Guo Jia quest. Count the sacrificed WATER Fractal in the future
  mill amount, then recompute at the target prompt after costs. Preserve it otherwise;
  avoid self-mill with graveyard replacement effects. Emptying the opponent's deck
  sets up their next failed draw; it is not treated as immediate game victory.

These rules use counts and visible information, never hidden hand identities or
ordered deck contents. Focused tests execute both seats and actual continuations;
seeded evaluation remains the check for full-game progress and observed coverage.

## Providence, Thurible, Incendiary Shot, and Clarent coverage

- Lost Providence's field activation uses the safe-draw policy (ready source, legal
  activation, no known draw blocker, and more than one card left in the deck).
  This does not add a policy for playing it from the material deck.
- Incendiary Shot uses the existing bullet-loading policy, now checking the engine's
  activation legality. It loads only with an available unloaded gun; the engine's
  mandatory target selection and loading continuation complete the action.
- Purifying Thurible keeps two cards in hand and pays at most three reserve. It
  pays three against a graveyard of at least three cards when the memory draw is
  safe, or clears a smaller graveyard containing floating memory or Guo Jia's water
  resources. Because the opponent chooses which cards to banish, this policy never
  assumes it can pick a particular opposing card. Its number prompt recomputes and
  bounds the payment. When affected by Thurible, the bot prefers to preserve its
  own floating memory and, for Guo Jia, water cards.
- Clarent, Sword of Peace responds to public, declared opposing targets for Mote
  Sear, Voltaic Sphere, and Thunderclap. It preserves the last durability unless
  its one point of prevention is estimated to save a unit, and avoids stacking
  existing noncombat protection. Unknown spells and combat alone do not trigger
  it. This is a conservative base-damage estimate, not a complete damage predictor:
  empower, other replacements, redirection, and every other spell are not modeled.

Focused tests run for both seats, including real cost/effect resolution. The
policies read only public cards, the bot's own hand, and deck counts. The frozen
baseline remains unchanged.

Validation on 2026-10-28: 305 decision checks, 31 hidden-information checks, and
three evaluation-harness tests passed. The full four-deck/five-seed/seat-swapped
suite completed all 160 games without failures against `BotBaselines/2026-10-28`.
The candidate won 83/160; this near-even result does not establish a strength gain.
Mean chooser time was 0.469 ms versus baseline 0.387 ms; this comparison includes
all changes since that baseline, including deck knowledge and earlier coverage.
The slowest per-game p95 was 5.482 ms versus 6.283 ms (not a pooled percentile).

Candidate activation offers/selected/declined were: Lost Providence 35/35/0,
Thurible 1883/8/1875, Incendiary Shot 34/29/5, Clarent 1611/0/1611. All four had
zero unsupported activation offers. Counts are chooser attempts, not unique
activations or proof of resolution. Clarent's beneficial resolution was exercised
by the focused tests, not selected in this match suite. Other prompts involving
these cards (for example a separate card's search effect) may remain unsupported.
