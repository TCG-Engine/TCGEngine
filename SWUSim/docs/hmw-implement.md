# HMW — Card Implementation Plan

**⚠ PREVIEW SET.** 28 cards exist (26 numbered + 2 tokens) of ~262 printed, as mock entries in
`AppCore/SWU/CardMocks.php`. Regenerate this plan (`swusim-generate-set-implement-doc HMW`) as more
previews land — the phases below cover only what is currently previewed.

21 cards total: 2 Leaders, 1 Base, 10 Units, 5 Upgrades, 2 Tokens (1 Token Unit, 1 Token Upgrade).
**18 needs-work, 3 auto-wired.**

### Already Done
HMW_019, HMW_T02, HMW_T03, HMW_009, HMW_004, HMW_061, HMW_095, HMW_081, HMW_121, HMW_171, HMW_085, HMW_127, HMW_142, HMW_234, HMW_257, HMW_177, HMW_255, HMW_059, HMW_158, HMW_206, HMW_060, HMW_164, HMW_162, HMW_193, HMW_014, HMW_115, HMW_116, HMW_136, HMW_124

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

- [x] **HMW_060 Vice Admiral Rampart** — DONE (ruling settled; see the dedicated section below). "If an
  upgrade on your base would be defeated, you may defeat this unit instead" is an interactive REPLACEMENT in
  `SWUDefeatUpgrade`. Final ruling: replaceable for **any** ability/cost/effect defeat of a base upgrade
  (HMW_081, HMW_095, HMW_171). ⚠ An earlier draft here claimed a cost/effect distinction citing a made-up
  "CR 3.13"; that was wrong — the SWU CR states a replacement effect CAN replace a cost (the cost still
  counts as paid) and the "If you do" payoff still resolves. Uniqueness enforcement only hosts on arena
  units, so it never reaches the base branch.

## Phase 2 — Entry triggers & "doesn't ready" (autonomous)

- [x] **Batch 2.1 — HMW_121, HMW_171** — done, 8 cases (2 + 6), suite 6010/0.
  - HMW_121 Hijacked AT-ST: Overwhelm (auto-wired keyword) + When Played — this unit doesn't ready
    during the next regroup phase. `$whenPlayedAbilities["HMW_121:0"]` self-targets HMW_095's
    `SWUSkipNextRegroupReady` (one-shot; NOT SOR_186's `SWU_CANT_READY_`).
  - HMW_171 Trap Field: Fortify; when a non-leader ground unit enters play (**including token units**)
    you may defeat this upgrade and deal 3 to that unit. **First base-hosted REACTIVE entry observer.**
    New seam `SWUCollectTrapFieldReactions($mzID)` (GameLogic.php) loops BOTH bases, arms one
    `AddTrigger($baseOwner,'HMW_171',...,uid,count)` per base with Trap Field; hooked at the played-unit
    funnel (`CollectEntryTriggers`, batches with the existing flush) AND the token funnel
    (`_SWUCreateOneToken`, explicit flush since token creation has none). Reaction owned by the base
    owner → cross-player when the enemy's base reacts (drains like SHD_172; the test needs an extra
    `AnswerDecision` to drain `RESOLVE_NEXT_TRIGGER` before the YESNO). Entered unit carried by UID
    (frame-independent). `DispatchTrigger` case → `Hmw171TrapFieldReaction` → `HMW_171#0` continuation
    (`SWUDefeatUpgrade('myBase-0')` + `SWUDealDamageToUnit(...,3)`; loops for the rare 2-Trap-Field base).
    HMW_171 has NO generated stub — "When a non-leader ground unit enters play" isn't matched by the
    WhenPlayed detection ("When Played:"/"When Deployed:" only), so the observer is wired by hand and
    stub-independent.
- [x] **Batch 2.2 — HMW_085, HMW_127** — done, 5 cases (2 + 3), suite 6015/0.
  - HMW_085 Remote Scout: When Played — `DoTopDeckSearch($player, 8, fn upgrade, 1)` (mirror SOR_125).
    Note: a no-match search still PRESENTS the TOPDECKSEARCH decision (the player looks at the top 8);
    choosing none (empty `AnswerDecision:`) draws nothing and bottoms all peeked cards — it does NOT
    auto-skip.
  - HMW_127 Chewbacca's Bowcaster: `$whenPlayedAbilities["HMW_127:0"]` gets the HOST mzID (non-pilot
    upgrade WhenPlayed fallback); if `CardTitle(host) === 'Chewbacca'` → `SWURampResourceExhausted(
    'myDeck-0')`. Attach restriction = HMW_127 added to the non-Vehicle attach group in
    `SWUGetUpgradeValidTargets`. The mock typo "Attach **of**" was corrected to "Attach to" in
    CardMocks.php (cosmetic only — attach is per-card case, not text-matched; no regen done).

## Phase 3 — Conditional keywords & base-trait conditions (autonomous)

- [x] **Batch 3.1 — HMW_142, HMW_234, HMW_257** — done, 8 cases, suite 6023/0.
  - Shared helper `_SWUControlsBaseWithTrait($player, $trait)` (GameLogic.php) — `HasTrait` resolves base
    traits (CardTraitSupplement backfill), verified against JTL_030 Mos Eisley (Tatooine).
  - HMW_142 Wookie Rangers: `HasConditionalKeyword_Sentinel` case — another Wookiee unit (`TraitContains`,
    self-excluded by UID) OR a Kashyyyk base. ⚠ **No Kashyyyk base is previewed in any set**, so that
    branch is currently unexercisable; it reuses the base-trait helper covered by HMW_234/HMW_177.
  - HMW_234 Ritual Dragon: Saboteur (auto-wired) + `_SWURitualDragonEntersReady` hooked in BOTH entry
    paths (ActivateCard unit-entry + `_SWUCreateOneToken` token path). "Including this one" = the helper's
    `$cardID === 'HMW_234'` self-clause (it isn't in play yet at entry-status time).
  - HMW_257 Ewok Archers: `HasConditionalKeyword_Ambush` case — another unit costing ≤3 (self-excluded;
    tokens cost 0 so qualify).
- [x] **Batch 3.2 — HMW_177, HMW_255** — done, 8 cases (5 + 3), suite 6031/0.
  - HMW_177 Adamant Ewoks: gate = another Ewok (`TraitContains`, self-excluded) OR Endor base
    (`_SWUControlsBaseWithTrait`). The base `SWUQueueMayChooseTarget` (`myBase-0`/`theirBase-0`) IS the
    "may" entry (decline = neither); `HMW_177#0` deals 1 to the chosen base then `SWUQueueChooseTarget`s
    the enemy-unit half (fizzles cleanly with no enemy unit).
  - HMW_255 C-3PO: two independent `SWUQueueMayChooseTarget` queued up front (`HMW_255#0`/`#1`), so
    declining/empty-first still offers the second. Any Ewok / any Rebel (no friendly qualifier).
    `SWUApplyPhaseBuff(...,'HMW_255')` (registered STAT_BUFF) stacks per-application; no Ewok is also a
    Rebel in the pool, so same-unit +4/+4 isn't exercisable. Phase-expiry verified.

## Phase 4 — Tokens (autonomous)

- [x] **Batch 4.1 — token-upgrade give path + HMW_059** — done, 3 cases, suite 6037/0.
  - New generic `DoGiveTokenUpgrade($player, $targetMZ, $tokenCardID)` (GameLogic.php) — the arbitrary-token
    generalisation the hardcoded Shield/Exp/Advantage givers lacked; token Owner/Controller follow the
    HOST's controller (so a Weakness on an enemy unit is an enemy upgrade). New `GIVE_WEAKNESS` continuation
    attaches HMW_T02 then runs `SWUCheckShrinkDefeats` (the -1 HP has no SBA of its own).
  - HMW_059 Clone X Assassin: `$whenDefeatedAbilities["HMW_059:0"]` → `GiveTokenUpgrade(token:'WEAKNESS',
    friendlyOnly:false, may:true)`. -1/-1 flows through the upgrade stat loop; enemy-attachable + lethal
    shrink both verified.
- [x] **Batch 4.2 — HMW_158** — done, 3 cases, suite 6037/0.
  - HMW_158 Ezra Bridger: the "when you take the initiative" offer is armed in `SWUTakeInitiative` (beside
    ASH_155/SEC_168). `HMW_158#0` deals 3 to your OWN base then gates the Beast (`SWUCreateUnitToken('HMW_T03')`)
    on the base damage actually rising (skipped when prevented — Close the Shield Gate verified). ⚠ Test note:
    Claim ends the round → seed decks or the empty-deck regroup penalty (+6 to each base) masks base assertions.

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
    use on nothing); the front side's `[2 resources, Exhaust]` cost changes game state, so it stays
    available and fizzles (matching TWI_009/TWI_012).
- [x] **Batch 5.2 — HMW_004 Grand Moff Tarkin** — done, 8 cases (3 pre-existing deploy + 5 new).
  - Both sides: "Ignore the aspect penalties on upgrades with Fortify you play" — one line at the
    `SWUAspectPenalty` chokepoint (the SOR_008 Hera / TWI_001 Nala Se shape), which covers every play
    path and the affordability glow at once. Scoped to `$Fortify_Cards`; a plain upgrade still pays.
  - ⚠ Test-design trap: Tarkin himself provides **Vigilance + Villainy**, and `PlayerAspects` counts a
    leader's aspects whether or not it is deployed — so a Vigilance Fortify upgrade (HMW_095) is
    on-aspect under him and proves nothing. The waiver test uses **HMW_171** (Aggression + Heroism,
    both uncovered, 2 + 4 = 6 → attaches on 2 resources only if waived).
  - Deployed side: "When the regroup phase starts: you may defeat a base with 10 or less remaining HP."
    **Resolved, not blocked** — defeating a base is not a distinct board state: a base with damage >= its
    HP IS defeated and its owner immediately loses (SWU CR, base section), so `SWUDefeatBase` fills the
    damage in and lets the existing `SWUCheckBaseDefeatState` sweep declare the outcome (which already
    handles Twin Suns seat elimination too). "A base" carries no friendly/enemy qualifier, so your own
    base is a legal — if suicidal — target, and the "10 or less remaining HP" wording is the multi-base
    filter that matters in Twin Suns.

## Phase 6 — Base-granted abilities (pair-programmed)

- [x] **Batch 6.1 — HMW_206 The Tarkin Doctrine** — done, 4 cases, suite 6041/0. **NOT blocked after all.**
  - The grant clause needed NO general "base-hosted granted abilities" framework — it's a targeted
    extension of the own-play-upgrade reaction path. `_SWUFinalizeUpgradeAttach` (the Fortify-play path)
    already calls `CollectWhenPlayedAsUpgradeTriggers`, so a one-line `AddTrigger('HMW_206')` there —
    gated on `HasTrait($cardID,'Fortification') && _SWUBaseHasUpgrade($player,'HMW_206')` — arms the
    "exhaust an enemy unit" reaction (DispatchTrigger `HMW_206` case). **Self-trigger ruling resolved by
    data:** HMW_206's own trait is `Law`, not `Fortification`, so playing The Tarkin Doctrine itself never
    triggers its own grant (guarded by a test).
  - When Played half: `$whenPlayedAbilities["HMW_206:0"]` — gate on `_SWUControlsTitle(['Grand Moff
    Tarkin'])`, then `APPLY_PHASE_DEBUFF|3|0|HMW_206` on an enemy unit (registered STAT_DEBUFF).

## HMW_060 Vice Admiral Rampart — DONE (ruling settled by the user 2026-07-30)

- [x] **HMW_060 Vice Admiral Rampart** — done, 3 cases, suite 6044/0. Interactive REPLACEMENT in
  `SWUDefeatUpgrade` (the 12-call-site chokepoint), deferred to action end via `$gDeferredReplacements`
  (`kind:'rampart_save'`) → `SWUFlushDeferredReplacements` → `RAMPART_SAVE` continuation (JTL_094 timing;
  HMW CR unreleased, user gave the ruling). **Ruling:** replaceable for ability/effect defeats (HMW_081);
  NOT for a COST-defeat (HMW_095 Action + HMW_171 self-sacrifice both now pass `$skipReplacement=true`).
  Uniqueness enforcement only hosts on arena units so it never reaches the base branch (no change needed).
  The subcard is stamped a UID at defer time so the flush re-finds it (`_SWUBaseUpgradeIndexByUID`). The
  cross-player deferred YESNO (defender reacts to the attacker's action) drains without an extra step.

## Status

**ALL 21 currently-previewed HMW cards are implemented.** The "no base-hosted granted abilities" (HMW_206)
and "no base-defeat primitive" (HMW_004) blockers both turned out to be non-blockers; HMW_060 landed once
the user settled the replacement-timing ruling.
