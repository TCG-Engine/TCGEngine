# HMW — Card Implementation Plan

**⚠ PREVIEW SET.** 21 cards exist (19 numbered + 2 tokens) of ~262 printed, as mock entries in
`SWUSim/Custom/CardMocks.php`. Regenerate this plan (`swusim-generate-set-implement-doc HMW`) as more
previews land — the phases below cover only what is currently previewed.

21 cards total: 2 Leaders, 1 Base, 10 Units, 5 Upgrades, 2 Tokens (1 Token Unit, 1 Token Upgrade).
**18 needs-work, 3 auto-wired.**

### Already Done
HMW_019, HMW_T02, HMW_T03, HMW_009, HMW_004, HMW_061, HMW_095, HMW_081

<!-- HMW_019 Dune Sea = blank-text base (52 of 92 released bases are likewise vanilla).
     HMW_T02 Weakness / HMW_T03 Beast = token CARDS; the engine handles tokens generically, so they
     get no per-card file. The ABILITIES that create them are HMW_059 / HMW_158 in Phase 4. -->

## Foundations already built (this session — do not re-do)

- **Fortify** — `HasKeyword_Fortify` + the `SWUGetUpgradeValidTargets` branch returning `['myBase-0']`
  + `Base.Subcards` + removal/uniqueness/observer coverage + the bottom-left count badge with its
  hover card-grid popup. 11 DSL cases in `Tests/Cases/keywords/Fortify.md`.
- **`SWUBaseIsUpgraded($player)` / `SWUBaseUpgradeCount($player)`** — what HMW_061 reads.
- **Base traits** — `CardTraitSupplement.php` backfills all 91 bases (the official API publishes none).
  HMW_142 (Kashyyyk), HMW_177 (Endor) and HMW_234 (Tatooine) depend on this.
- **Token plumbing that already generalizes:** `SWUCreateUnitToken($player, 'HMW_T03')` creates a token
  unit by CardID — Beast needs no new subsystem.

⚠ **Read subcards through `GetUpgradesOnUnit($obj)`, never `$sub->CardID`.** After a gamestate
round-trip `Subcards` decode as associative ARRAYS; direct property reads return null and
`(string)$sub` emits a warning that corrupts the response stream. This cost a live-game bug already.

## Phase 1 — Fortify cards + base-upgrade readers (autonomous)

- [x] **Batch 1.1 — HMW_061, HMW_095** — done, 9 cases, suite 5995/0.
  - HMW_061 Director Krennic: On Attack — if your base is upgraded, draw a card. One-liner over
    `SWUBaseIsUpgraded()`; guarded against reading "any base" instead of "your base".
  - HMW_095 Carbonite Chamber: Fortify; `Action [defeat this upgrade]:` choose a non-Vehicle unit — it
    doesn't ready during the next regroup phase.
  - **New seam: base-hosted activated Actions.** `$baseUpgradeAbilities[$upgradeCardID] =
    fn($player, $upgradeIndex)` + `_SWUBaseActionProviders()` / `_SWUDispatchBaseAction()`;
    `SWUBaseAction` is now a dispatcher over that list. Clicking the base is ONE undifferentiated input,
    so when a base has both its own Epic and a Fortify-upgrade Action the player gets an OPTIONCHOOSE
    (`BASE_ACTION_PICK`) rather than one silently shadowing the other — the deliberate difference from
    the single-provider unit model. Labels are single-token and re-derived, so no state rides the
    decision. Fixed en route: clicking a **vanilla** base used to set `EpicActionUsed` on a base that
    has no Epic at all; the provider list only offers Actions that exist.
  - **New marker: `SWU_SKIP_REGROUP_READY_{uid}`** (`SWUSkipNextRegroupReady($mz)`), read+consumed only
    by the regroup ready loop. Deliberately NOT SOR_186's `SWU_CANT_READY_`, which also blocks explicit
    mid-phase "ready a unit" effects — a scope guard test pins the difference. Batch 2.1's HMW_121
    reuses this.
- [x] **Batch 1.2 — HMW_081** — done, 4 cases, suite 5999/0. **HMW_060 DEFERRED (see below).**
  - HMW_081 Alliance Shield Generator: Fortify; if the attached base would be dealt 5+ damage, prevent
    it, then defeat this upgrade and draw. Sits in `SWUDealDamageToBase` beside JTL_074 / ASH_070 and
    reuses their hoisted `$baseDmgUnpreventable`, so indirect / ASH_196 damage lands in full — and,
    being conditional rather than a one-shot shield, a sub-threshold hit neither triggers nor consumes
    it. Ordered BEFORE the ASH_070 cap: capping 6 → 4 first would silently disarm the generator, and
    full prevention plus a draw is what that player would choose anyway (both effects are theirs).
  - New helper `SWUFindUpgradeIndex($obj, $cardID)` → the `$upgradeIndex` `SWUDefeatUpgrade` expects.

- [ ] **HMW_060 Vice Admiral Rampart** ⛔ DEFERRED — needs a ruling, not more code
  - "If an upgrade on your base would be defeated, you may defeat this unit instead." A genuine
    interactive REPLACEMENT inside `SWUDefeatUpgrade`, which is synchronous, returns a bool, and has 12
    call sites. The mechanics are reachable (queue the owner's YESNO, return early un-mutated, and let
    the continuation either defeat Rampart or re-enter with `$skipReplacement`), but which call sites may
    be replaced is a CR judgment per site, and two of them are genuinely arguable:
    - **Uniqueness enforcement** (3 sites) must NOT be replaceable — saving the duplicate would leave a
      player controlling two copies of a unique upgrade, an illegal state. Clear.
    - **HMW_095's own `Action [defeat this upgrade]`** is a COST. CR 3.13 says paying a cost is not an
      effect, so it should not be replaceable — otherwise Rampart lets you use the Chamber for free.
    - **HMW_081's "If you do, defeat this upgrade"** is NOT a cost — it is part of the effect, so by the
      text Rampart *should* be able to save the generator (a one-time save; Rampart is unique). That
      makes the prevention non-atomic, which is why it wants an explicit decision rather than a guess.
  - Recommendation: allow the replacement for ability-driven defeats, block it for the uniqueness rule
    and for HMW_095's cost, and confirm the HMW_081 interaction before wiring.

## Phase 2 — Entry triggers & "doesn't ready" (autonomous)

- [ ] **Batch 2.1 — HMW_121, HMW_171**
  - HMW_121 Hijacked AT-ST: Overwhelm (auto-wired keyword) + When Played — this unit doesn't ready
    during the next regroup phase. Same marker as HMW_095, self-targeted.
  - HMW_171 Trap Field: Fortify; when a non-leader ground unit enters play (**including token units**)
    you may defeat this upgrade and deal 3 to that unit. A base-hosted REACTIVE entry observer —
    verify it fires for tokens (`SWUCreateUnitToken`) and not just played units, and for BOTH players'
    units (the text is unrestricted).
- [ ] **Batch 2.2 — HMW_085, HMW_127**
  - HMW_085 Remote Scout: When Played — search the top 8 for an upgrade, reveal, draw it; others to the
    bottom in random order. Mirrors SOR_125 (search 8 for Vehicle units).
  - HMW_127 Chewbacca's Bowcaster: When Played — if the attached unit is Chewbacca, resource the top
    card of your deck (enters exhausted). ⚠ Its mock text has an upstream typo, "Attach **of** a
    non-Vehicle unit"; fix to "Attach to" first — that phrase is what attach logic pattern-matches.

## Phase 3 — Conditional keywords & base-trait conditions (autonomous)

- [ ] **Batch 3.1 — HMW_142, HMW_234, HMW_257**
  - HMW_142 Wookie Rangers: while you control another Wookiee unit **or a Kashyyyk base**, gains
    Sentinel → `HasConditionalKeyword_Sentinel`.
  - HMW_234 Ritual Dragon: Saboteur (auto-wired) + while you control a **Tatooine base**, friendly
    units enter play ready (**including this one** — so it must apply to its own entry).
  - HMW_257 Ewok Archers: while you control another unit costing 3 or less, gains Ambush →
    `HasConditionalKeyword_Ambush`.
  - All three read a BASE trait or a cheap-unit count; the conditional-keyword functions live in
    DIFFERENT files per keyword, so grep the CardID after implementing and confirm the hit count
    matches the branch count.
- [ ] **Batch 3.2 — HMW_177, HMW_255**
  - HMW_177 Adamant Ewoks: When Played — if you control another Ewok unit **or an Endor base**, you may
    deal 1 to a base and 1 to an enemy unit. Two targets, one optional gate.
  - HMW_255 C-3PO: When Played — you may give an Ewok unit +2/+2 for this phase; you may give a Rebel
    unit +2/+2 for this phase. TWO INDEPENDENT may-choices (declining the first must still offer the
    second), and per-application phase buffs must use `_SWUStackingStatToken` so two buffs on one unit
    stack rather than de-dup.

## Phase 4 — Tokens (autonomous)

- [ ] **Batch 4.1 — token-upgrade give path + HMW_059**
  - **Foundation first:** `DoGiveExperienceToken` hardcodes `SOR_T01`, so there is no way to attach an
    arbitrary token upgrade. Generalize it (or add a sibling) plus a `GIVE_WEAKNESS` DQ continuation so
    `GiveTokenUpgrade(['token' => 'WEAKNESS'])` works — that helper already builds the continuation
    name as `GIVE_{TOKEN}`.
  - HMW_059 Clone X Assassin: When Defeated — you may give a **Weakness token** (HMW_T02) to a unit.
    The −1/−1 needs no stat code: it comes from the token's `upgradePower`/`upgradeHp`, which the
    upgrade stat loop already reads. ⚠ Assert a unit reduced to 0 HP by it is defeated
    (`SWUCheckShrinkDefeats`), and that it is attachable to an ENEMY unit (the text says "a unit").
- [ ] **Batch 4.2 — HMW_158**
  - HMW_158 Ezra Bridger: when you take the initiative — you may deal 3 damage to YOUR OWN base; if you
    do, create a **Beast token** (`SWUCreateUnitToken($player, 'HMW_T03')`). Needs a "when you take the
    initiative" observer on `SWUTakeInitiative`; check whether one exists before adding. Self-damage is
    the cost, so the "if you do" gate must not fire when the damage is prevented.

## Phase 5 — Leaders (pair-programmed)

- [x] **Batch 5.1 — HMW_009 Chewbacca** — done, 5 cases, suite 5986/0.
  - Front `Action [2 resources, Exhaust]:` attack with a unit **even if it's exhausted**; it can't
    attack bases for that attack. Deployed side: the same attack, **once each round**, with no
    resource cost.
  - Both design forks turned out to be already-built seams (SEC_103 Mon Mothma): `BeginSWUAttack` has
    no ready requirement at all, so "even if it's exhausted" is just a matter of not filtering the
    attacker pool by `Status`, and `$noBases = true` is its third parameter. Shared
    `_SWUHmw009Attackers` + one `HMW_009#0` continuation serve both sides; they differ only in cost.
  - The once-each-round budget is the leader unit's **NumUses** (refreshed by `SWUResetAllNumUses` at
    RegroupPhaseStart), not a bespoke `SWU_*_USED` flag.
  - Asymmetric target-gating, deliberate: the deployed Action has NO cost, so it is gated in
    `SWUUnitActionAffordable` on a legal attacker existing (otherwise activating would burn the round's
    use on nothing); the front side's `[2 resources, Exhaust]` cost changes game state, so per
    CR 6.4.587.c it stays available and fizzles (matching TWI_009/TWI_012).
- [x] **Batch 5.2 — HMW_004 Grand Moff Tarkin** — done, 8 cases (3 pre-existing deploy + 5 new).
  - Both sides: "Ignore the aspect penalties on upgrades with Fortify you play" — one line at the
    `SWUAspectPenalty` chokepoint (the SOR_008 Hera / TWI_001 Nala Se shape), which covers every play
    path and the affordability glow at once. Scoped to `$Fortify_Cards`; a plain upgrade still pays.
  - ⚠ Test-design trap: Tarkin himself provides **Vigilance + Villainy**, and `PlayerAspects` counts a
    leader's aspects whether or not it is deployed — so a Vigilance Fortify upgrade (HMW_095) is
    on-aspect under him and proves nothing. The waiver test uses **HMW_171** (Aggression + Heroism,
    both uncovered, 2 + 4 = 6 → attaches on 2 resources only if waived).
  - Deployed side: "When the regroup phase starts: you may defeat a base with 10 or less remaining HP."
    **Resolved, not blocked** — defeating a base is not a distinct board state: a base at or above its
    printed HP in damage IS defeated and its controller loses (CR 3.2.5), so `SWUDefeatBase` fills the
    damage in and lets the existing `SWUCheckBaseDefeatState` sweep declare the outcome (which already
    handles Twin Suns seat elimination too). "A base" carries no friendly/enemy qualifier, so your own
    base is a legal — if suicidal — target, and the "10 or less remaining HP" wording is the multi-base
    filter that matters in Twin Suns.

## Phase 6 — Base-granted abilities (pair-programmed)

- [ ] **Batch 6.1 — HMW_206 The Tarkin Doctrine** ⛔ blocked on a new capability
  - Fortify; **"Attached base gains: 'When you play a Fortification upgrade: Exhaust an enemy unit.'"**
    An upgrade granting a triggered ability TO A BASE does not exist — base-hosted granted abilities
    are new, and the granted trigger keys on playing a **Fortification-trait** upgrade.
  - Plus When Played: if you control Grand Moff Tarkin, give an enemy unit −3/−0 for this phase (that
    half is ordinary, and depends on HMW_004 existing for a full test).

## Blocked summary

| Card | Blocker |
|---|---|
| HMW_206 (grant clause only) | no base-hosted granted abilities; its When Played half is ordinary |

HMW_004's "no base-defeat primitive" blocker is **resolved** — see Batch 5.2. `SWUDefeatBase($player)` /
`SWUBaseRemainingHp($player)` in `GameLogic.php` are now available to any later card.

17 of 18 needs-work cards are implementable today; the one remaining blocker affects no other card.
