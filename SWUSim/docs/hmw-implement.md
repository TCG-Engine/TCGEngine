# HMW — Card Implementation Plan

**⚠ PREVIEW SET.** 68 cards exist (66 numbered + 2 tokens) of ~262 printed — as of the fifth wave,
2026-08-19 (HMW_035 / HMW_074 / HMW_272 landed then) — as mock entries in
`AppCore/SWU/CardMocks.php`. Regenerate this plan (`swusim-generate-set-implement-doc HMW`) as more
previews land — the phases below cover only what was previewed when each was written.

⚠ The phase batches below cover the ORIGINAL 21 cards. Cards previewed later were implemented
individually and appear only on the `### Already Done` line — that line, diffed against the HMW
entries in `CardMocks.php`, is the authoritative "what is left" check. (Counting batches instead
would have reported this set complete while HMW_003 was still unimplemented.)

### Already Done
HMW_019, HMW_T02, HMW_T03, HMW_009, HMW_004, HMW_061, HMW_095, HMW_081, HMW_121, HMW_171, HMW_085, HMW_127, HMW_142, HMW_234, HMW_257, HMW_177, HMW_255, HMW_059, HMW_158, HMW_206, HMW_060, HMW_164, HMW_162, HMW_193, HMW_014, HMW_115, HMW_116, HMW_136, HMW_124, HMW_003, HMW_062, HMW_064, HMW_070, HMW_020, HMW_021, HMW_023, HMW_024, HMW_026, HMW_027, HMW_028, HMW_029, HMW_030, HMW_031, HMW_033, HMW_034, HMW_188, HMW_043, HMW_147, HMW_200, HMW_048, HMW_007, HMW_107, HMW_202, HMW_077, HMW_110, HMW_114, HMW_118, HMW_176, HMW_084, HMW_113, HMW_045, HMW_123, HMW_151, HMW_010, HMW_117, HMW_074, HMW_272, HMW_035, HMW_055, HMW_196, HMW_017, HMW_210

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

## Phase 7 — the second preview wave (autonomous)

⚠ Read this before trusting any earlier "all done" claim: **every batch above was `[x]` and the Status
below read "ALL 33 … are implemented" while 13 further cards sat unimplemented.** The batches only ever
covered the cards previewed when they were written, and a preview set GROWS. The oracle is the diff
(`### Already Done` vs `grep -oE "'HMW_[0-9T]+'" AppCore/SWU/CardMocks.php`), never the checkboxes.

- [x] **Batch 7.1 — HMW_020/021/023/024/026/027/028/029/030/031/033/034** — 12 blank-text 30-HP bases
  (Naboo / Kashyyyk / Endor / Tatooine), verify-only no-ops per the Step-0 vanilla triage. Their TRAITS
  are the payload, and they unblocked a branch that had been unexercisable since Phase 3:
  - **HMW_142 Wookie Rangers' "or a Kashyyyk base" Sentinel branch** — 3 new cases (suite 7042→7045):
    the positive via HMW_021 Kashirho, the controller-scoping negative (the OPPONENT holding Kashirho
    grants nothing), and a trait-scoping negative (HMW_020 Great Grass Plains, the same vanilla shell
    with the NABOO trait, grants nothing). Paired against the pre-existing ordinary-base negative on an
    identical board, so the base is provably the only differentiator. No code change — the branch was
    already correct, just unreachable; the stale "unexercisable" comments in `KeywordEffects.php` and
    the test file were corrected.
  - HMW_177 (Endor) and HMW_234 (Tatooine) already had base-trait coverage via JTL_020 / JTL_030.
- [x] **Batch 7.2 — HMW_188 Giant Gorax** — 17 cases, suite 7045→7062/0. **Plus one ENGINE bug.**
  - Overwhelm is free from the keyword registry. `$onAttackAbilities` and `$whenDefeatedAbilities` share
    ONE closure gated on `_SWUControlsBaseWithTrait($player,'Endor')`, evaluated against the RESOLVER.
  - The closure only queues an intermediate `CUSTOM` (`HMW_188#0`) — DispatchTrigger/OnAttackTrigger
    restore `$playerID`, so the cross-player OPTIONCHOOSE and every relative-mzID pick must be queued
    from a continuation (the LAW_080 shape). That is also what makes the Deal3 pick safe as a MANDATORY
    MZCHOOSE inside an On Attack. The caster rides the CUSTOM's own Param, so the chain survives the
    request boundary the opponent's decision creates (guarded by a `SimulateRequestBoundary` section).
  - Option B reuses `SWUDiscardCards` + SOR_017 Han Solo's bare-`myResources` MZCHOOSE →
    `HAN_DEFEAT_RESOURCE`. The two halves are joined by AND, not "if you do": empty hand still defeats a
    resource, and no resources still discards — both covered.
  - **★ ENGINE BUG — combat damage committed ahead of a still-pending pre-damage decision.** The
    `SWU_TRIGGER_RESUME` COMBAT branch hops onto the non-active player's queue when THEY owe a blocking
    decision (the On Defense pause). But a cross-player chain can bounce the decision BACK: the opponent
    picks the mode, then the CASTER picks the damage target. Once hopped, the resume could no longer see
    the active player's pending pick, so it committed combat — and the target then resolved against a
    board combat had already changed (Gorax's 3 damage landed on a unit combat had just defeated, and
    Overwhelm spilled the wrong number). Fixed with a symmetric hop-back, guarded on
    `$player !== $activePlayer` so a resume sitting behind the active player's own block can't re-queue
    itself forever. It uses a new **`_SWUPlayerHasPendingWork()`** rather than
    `_SWUPlayerHasBlockingDecision()` — an auto-resolving `PASSPARAMETER` (what the caster's pick becomes
    when the opponent controls only a base) is not "blocking" but is still pre-damage work.
  - ⚠ Harness note: the opponent's answer drains only THEIR queue, so a section that ends on a
    cross-player answer needs a trailing `P1>Drain` (the stand-in for production's post-action drain) or
    the caster's auto-resolving pick never runs.

## Status

**Card-complete for the FOURTH wave (2026-08-14).** `CardMocks.php` now holds **56** HMW
CardIDs. Batches 9.1-9.4 closed ALL FIVE (HMW_107, HMW_202, HMW_077, HMW_110, HMW_114); the diff is empty as of 2026-08-14. Everything below
this line describes the state as of the THIRD wave and is retained as history — re-derive the real
number with the diff, never by reading a Status line or counting batches.

**(historical, 2026-08-12)** All 46 then-mocked HMW cards were implemented (verified by diffing the
`### Already Done` line against the HMW entries in `AppCore/SWU/CardMocks.php`, not by counting batches).
The "no base-hosted granted abilities" (HMW_206) and "no base-defeat primitive" (HMW_004) blockers both
turned out to be non-blockers; HMW_060 landed once the user settled the replacement-timing ruling.
**This number is only true until the next preview wave lands — re-run the diff, don't re-read this line.**

**HMW_003 Doctor Hemlock (2026-08-12)** — 15 sections, suite 7004→7019/0. Leader, both sides:
front `Action [1 resource, Exhaust]` gives a Weakness token to a unit *without* one (the exclusion is a
TARGET FILTER, asserted on the offer via `P1SELECTABLEEXACT`); deployed `On Attack` may give one with
**no** exclusion, so it can stack a second -1/-1 — that asymmetry is printed and is why the two sides
cannot share a filter. The Epic Action needed **zero code**: the generic deploy threshold already equals
the leader's printed cost (6), pinned by a 6-vs-5 boundary pair. Reused `GIVE_WEAKNESS` +
`DoGiveTokenUpgrade` from Phase 4 — no new infrastructure.

**HMW_062 / HMW_064 / HMW_070 (2026-08-12)** — 23 sections, suite 7019→7042/0. A regen was required
first: none of the three had generated code yet, so `HMW_062`/`HMW_064` had no trigger stub (handlers
would have silently never fired) and `HMW_070` was absent from `$Fortify_Cards` (it could not attach to
a base at all). The regen was drift-checked against a pre-copy — only the 3 new cards plus index
renumbering, zero change to existing card data — and the suite was re-verified green BEFORE any card
code was written.
- **HMW_064 Scorch** — On Attack, may deal 1 to an upgraded unit. `_SWUIsUpgraded` counts token
  upgrades, so a Shield-only unit qualifies (and its shield then absorbs the 1 — covered).
- **HMW_070 Dark Sanctum** — Fortify (free from the keyword registry) + a base-granted regroup trigger
  hooked in `RegroupPhaseStart` beside HMW_004's. Fires once PER ATTACHED COPY (non-unique), and its
  self-damage can defeat your own base — covered by a 28/27 boundary pair.
- **HMW_062 Nuvo Vindi** — When Played, plus "when an enemy unit WITH A WEAKNESS TOKEN is defeated".
  That condition cannot be read at reaction time (subcards are stripped by then), so a `'weakened'`
  key was added beside the existing `'upgraded'` capture at all **6** defeat-entry sites — the same
  shape SHD_137 relies on. Observer + once-each-round gate live in `SWUCollectLeavePlayReactions`;
  the flag is consumed at collect time so declining still spends the round, and it is cleared in
  `RegroupPhaseStart`. Both the combat AND effect (`SWUDefeatUnit`) defeat paths are covered.


## Phase 8 — third preview wave (autonomous)

- [x] **Batch 8.1 — HMW_043 Darth Vader, Any Methods Necessary** — 12 sections, suite 7066 → 7078, 0 failed.
  Saboteur was free (already in `$Saboteur_Cards`, generic keyword coverage exists); the When Played half
  is new. **2 gate bugs found and fixed during the build**, both in this card's own filter — see below.

### ⚠ Set status after Batch 8.1
`### Already Done` now covers **47 of 47** HMW CardIDs in `CardMocks.php`. Re-derive with the diff, never
the checkboxes: `grep -oE "'HMW_[0-9T]+'" AppCore/SWU/CardMocks.php` vs the Already Done line, then a
plain-substring `grep -rn "<CardID>" SWUSim/Custom/` for anything it surfaces (registration keys use
DOUBLE quotes — `$leaderAbilities["HMW_003"]` — so a `'HMW_003'`-quoted grep reports false gaps).

### ✅ Fixed during the build — the search filter was FRONTEND-ONLY
`_topDeckSearchBegin($player, $n, $filter, …)` uses `$filter` only to build the `matchIDs` hint sent to
the client. The finalize resolves the answer via `_topDeckResolveFromIDs($allIDs, …)` — against **every
peeked card**, not the matching ones. So any pick that reaches the handler is honoured. Before the fix,
answering HMW_043's search with a cost-5 unit played it, and answering with a cost-3 **event** placed the
event into the ground arena as a unit. `_SWUHmw043IsLegalPick()` is now THE gate, used to build the offer
AND re-checked per pick in the handler; illegal picks join the cards going to the bottom.
✅ **CLOSED (same session) — and it was set-wide, not 4 cards.** ALL **20** `_topDeckSearchBegin` call
sites were filter-advisory, not just the `SOR_087#0` family. Fixed centrally instead of per card:
`_topDeckSearchBegin` now stores the match list (`TopDeckLegalIDs`, `~`-sentinelled) and the constraint
(`TopDeckConstraint`), and `_topDeckResolveFromIDs` — which all 20 finalize handlers share — re-applies
both. One store + one check fixes every caller with no signature churn. Illegal/overflow picks fall
through to `remaining`, the disposition callers already give an unpicked card (bottom of deck).
⚠ **The `~` sentinel is load-bearing**: an EMPTY match list means "a filter is in force and nothing
matched", not "unrestricted". My first cut treated empty as unrestricted, which is the exact inversion —
and it is the common case (a search whose top N holds no legal card). It was caught only because
`IllegalPick_NonUnitEvent_IsRefused` seeds a deck of pure events.
⚠ **A second, independent hole in the same place:** the CONSTRAINT (`count:N` / `cost:N` / `cost:N:M`) was
client-only too. SOR_087's "combined cost 3 or less" played two cost-2 units for a combined 4. Now
enforced in pick order, dropping the overflow. Guarded by
`sor/DarthVader_CommandingTheFirstLegion.md::SearchFilter_CombinedCostBudgetIsENFORCED` (mutation-verified)
and its partner `SearchFilter_NonVillainyPickIsREFUSED` for the filter — two mechanisms, two sections,
because fixing either alone leaves the other open.

### ⚠ DEFERRED (NOT a bug fix) — "play them for free" is a PUT INTO PLAY across this whole family
`SOR_087#0` places fetched units with a bare `AddGroundArena`/`AddSpaceArena`, so the fetched unit's own
**When Played does not fire** and no entry ceremony runs (no `FlushEntryTriggerBag`, no Shielded, no
Ambush, no uniqueness sweep). Every card in the family is printed "play them for free", and by the rules
playing a unit from the deck IS playing it — so those abilities should fire. HMW_043 deliberately matches
its five siblings rather than being the single card that behaves differently.
Correcting it is a six-card change (SOR_087, LAW_063, ASH Ackbar, SOR_104, HMW_043 + the shared handler)
and it re-introduces an ORDERING hazard this card currently dodges: once a play can queue decisions
(When Played / Shielded / Ambush / uniqueness), HMW_043's inline "deal 2 damage to each" would run BEFORE
them and re-index the arena underneath a pending offer — the SEC_018 family. The handler's comment says
so, so whoever does that pass is warned at the call site.

### Load-bearing checks (mutation-verified)
Each gate was removed in turn and the expected section failed, nothing else:
| mutation | section that caught it |
|---|---|
| drop the server-side filter re-check | both `IllegalPick_*` |
| cost cap 4 → 5 | `IllegalPick_UnitCostingFIVE_IsRefused` |
| search depth 8 → 9 | `Top8Depth_NinthCardIsNotReachable` |
| damage arena slots instead of the recorded UIDs | 6 sections incl. `TwoDamage_HitsONLYTheUnitsPlayedThisWay` |

### ✅ LOF_100 Kelleran Beq converted to a REAL play (2026-08-13, user-directed)
Audit of the nine search-and-play cards found Kelleran Beq was the odd one out inside its own wording
family. SHD_194 Triple Dark Raid and LAW_074 Maz Kanata are printed identically — "search … and play it.
It costs N less" — and both route through the real play path (`ActivateCard` / `SWUPlayTopDeckCard`).
LOF_100 used a bare `AddGroundArena`, so the fetched unit's **When Played never fired**, and no entry
ceremony ran. It now uses the LAW_074 idiom (chosen card to the top of the deck, then
`SWUPlayTopDeckCard(..., false, 3)`), which also gives it affordability through the real cost pipeline.
Two guards added, both mutation-verified:
- `FetchedUnitFiresItsOwnWhenPlayed` — SHD_080 Salacious Crumb's mandatory "heal 1 from your base" fires
  (base 5 → 4). Under the old placement it stayed at 5.
- `FetchedPILOTingUnitIsOfferedTheUnitVsPilotChoice` — the **card-vs-unit** distinction. Kelleran searches
  for "a UNIT" and a Piloting card IS a unit card, so it is a legal find; but once you are PLAYING it, it
  may be played as a unit or as a Pilot upgrade. `SWUPlayTopDeckCard` detects Piloting and routes to
  `SWUBeginPlayCard`; the old path could only slam it into the arena as a unit.
  ⚠ Resources are load-bearing in that fixture: at 7 (all spent on Kelleran) the 1-resource PILOT cost is
  unaffordable, so the engine correctly drops the pilot option and the play auto-resolves with NO prompt.
  10 is what makes the choice reachable — an under-resourced fixture passes for the wrong reason.
  Mutation: flipping `SWUPlayTopDeckCard`'s `$ignoreCost` to true skips the Piloting routing and fails
  exactly this section.

### ✅ ASH_090 Reforge — Pilot-exclusion guard added (the mirror case)
Reforge searches for "an UPGRADE", so the very same Piloting card must NOT be found: that filter is a
card-TYPE test, and a Piloting card's type is `Unit`. Already correct in code; it now has
`SearchExcludesPILOTUnitCards` (JTL_215 BoShek over a SEC_214 Vehicle host — a host BoShek can genuinely
attach to, so only the type gate excludes him).
⚠ Note the existing `SearchExcludesUnitCards` (plain unit SOR_051) also fails if the type gate is deleted
outright — `SWUGetUpgradeValidTargets` falls back to "all friendly units" for an unknown CardID. The Pilot
section earns its place against a filter that is WRONG rather than absent: swap the type gate for a
can-this-attach test and SOR_051 is still excluded while BoShek sails through.

### Audit result for the whole search-and-play family (9 cards)
All nine route through `_topDeckSearchBegin`, so all inherit the server-side filter + constraint
enforcement. Placement splits three ways: **free → put-into-play** (SOR_087, SOR_104, SHD_123, LAW_063,
ASH_110 — consistent, and the When-Played deferral below still applies to them); **discounted → real
play** (SHD_194, LAW_074, and now LOF_100); **upgrade attach** (ASH_090, via
`_SWUFinalizeUpgradeAttach`). Numbers were re-checked against the dictionary text and all match.

## Phase 9 — fourth preview wave (autonomous; HMW_048 PARKED by user direction)

- [x] **Batch 9.1 — HMW_147 Beast Lair + HMW_200 Rish Loo** — 11 sections, suite 7120 → 7131, 0 failed.
  Card data was regenerated first (`zzCardCodeGenerator.php rootName=SWUSim` → `cardArrayCache.json`,
  then `Data/ProcessKeywordsSWU.php` for the keyword registries — ⚠ `zzGameCodeGenerator.php` alone does
  NOT refresh `$Fortify_Cards`/`$Hidden_Cards`).
  - **HMW_147 Beast Lair** (6): Fortify half free; the granted half is a base-hosted ACTION-phase-start
    trigger (`_SWUHmw147ActionPhaseTriggers` hooked in ActionPhaseStart — the phase-mirror of HMW_070's
    regroup hook). Per-copy, mandatory discard with player card-choice, "if you do" Beast (HMW_T03).
    ⚠ Harness: crossing a full round needs `P1>Pass / [P2>Pass] / P1>ResourcePass / P2>ResourcePass /
    P{n}>Drain` — the resource prompt appears even with an EMPTY hand (zone-form offer). Mutation:
    unhooking the trigger fails 4 sections.
  - **HMW_200 Rish Loo** (5): Hidden free; mandatory steal of a weakened enemy non-leader
    (`SWUQueueChooseTarget`, single auto-resolves), give-back at regroup start via the JTL_235-shaped
    PERM per-UID global (`SWU_HMW200_RETURN_<uid>`) — returning CONTROL, not the card (block lives
    beside Commandeer's in RegroupPhaseStart). Offer section excludes non-weakened / weakened-friendly /
    weakened-deployed-leader in one SELECTABLEEXACT. Note: the explicit `IsLeaderUnit` check is masked
    by `NonLeaderUnitFilter` (defense-in-depth, not load-bearing — the offer test pins the behaviour).

### ✅ HMW_048 Vernestra Rwoh — DONE (2026-08-13). Set status: **50 of 50** — HMW is card-complete.
11 sections, suite 7131 → 7143/0 (with the LOF_197 Ambush hardening). No new framework was needed:
- **Additional cost** = Exploit's play-path shape (offer in `_SWUBeginPlayCardUnitPath`, resolve in
  `HMW_048#0`, continue via `SWUContinuePlayAfterExploit`). Both queue entries are `dontSkipOnPass` —
  cost + orchestration must survive a sticky PASS. The consume-once play-grant globals
  (`gForceEnterReady`/`gPlayGrantTurnEffect`/`gPlayGrantShield`) are SNAPSHOTTED INTO THE PARAM and
  restored in the handler — the caller nulls them long before the queued cost resolves.
- **Gains** ride an SWUVar (`SWU_HMW048_GAINS`) from the cost step to `CollectEntryTriggers`, which
  stamps `SWU_HMW048_GAIN_<CID>` on her (phase sweep = the "for this phase" expiry) and bags one
  `HMW048Gain` trigger per donor with a registered `$whenPlayedAbilities` closure. Dispatch reuses
  `OnWhenPlayed` with HER mzID — so "this unit" = her, multiple gains order via the normal prompt, and
  a gained ability counts as "using a When Played ability" for LOF_197's repeat.
- **Rulings applied**: Shielded/Ambush are NOT When Played abilities (donor keyword-only = gains
  nothing; hardened generically on LOF_197 with `NoRepeat_AmbushKeyword` beside the existing Shielded
  section); bottom order RANDOM (`_topDeckPutRemainingToBottom` shuffles); gains resolve via the bag.
- Mutations: cost cap 5→6 fails exactly the offer section; collection hook off fails 4.

### ⚠ OPEN ENGINE-FAMILY FOLLOW-UP — additional costs are SKIPPED on direct-ActivateCard nested plays
A play dispatched straight through `ActivateCard` (SOR_219 Sneak Attack, play-from-deck effects) never
passes `_SWUBeginPlayCardUnitPath`, so it skips **Exploit** — and now Vernestra's additional cost, which
deliberately matches that scope rather than hacking one card past it. ⚠ Severity note (user,
2026-08-13): because the cost is "UP TO 2", zero is a legal payment — so the nested-path skip is an
implicitly FORCED zero, not an illegal play. The gap loses the player an OPTION (and Vernestra her
gains); it does not break the play's legality. Same logic applies to Exploit ("up to X"). Lower
severity than first framed, still worth the one-seam fix. An in-drain fix needs care: a queued cost prompt gets eaten by the outer answer's
sticky drain (probed: block-2 + dontSkipOnPass was not sufficient; the mid-drain MZMULTICHOOSE consumed
the stale `myHand-0`). Fix the family at ONE seam; the section that must FLIP when fixed is
`VernestraRwoh::NestedDirectPlay_SkipsTheAdditionalCost_LikeExploit` (its comment says so).

## Phase 9 — fourth preview wave (autonomous)

Wave landed 2026-08-14: HMW_077, HMW_107, HMW_110, HMW_114, HMW_202 (mock art + `CardMocks.php`
entries; the `zzCardCodeGenerator` + `ProcessKeywordsSWU` regen had already been run, so all five were
in the dictionary and keyword registries before implementation started). Suite baseline 7785/0.

- [x] **Batch 9.1 — HMW_107 Stormtrooper Patrol + HMW_202 Inferno Squad, We Can Grieve Later** —
  21 sections, suite 7785 → 7806, 0 failed. No new infrastructure; no engine bugs found.
  - **HMW_107** — Sentinel was FREE (already auto-registered in `$Sentinel_Cards` by the keyword
    generator). The real work is the rider, "While you control another unit that costs 3 or more, this
    unit gets +2/+0" — a continuous self-passive in `ObjectCurrentPower` beside the TWI_163/TWI_130
    family, power only, gated on `!$lost`. Three printed details each got their own guard: cost is the
    PRINTED cost (a 0-cost token ally never qualifies), "another" excludes self by **UniqueID** so two
    copies each buff the other, and "you control" means an enemy 3+ cost unit grants nothing. A
    DEPLOYED LEADER does qualify — `GetUnitsInPlay` reads the arenas directly, so it is included; a
    printed-CardType `'Unit'` filter would have wrongly excluded it (leader units are CardType
    `'Leader'`). Covered the aura ENDING (ally trades in combat → back to 2 power), which is the cell a
    permanent-buff bug would sail past.
  - **HMW_202** — When Played **and** When Defeated on one shared closure so the two windows cannot
    drift. "a unit" is unqualified → friendly, enemy and ITSELF are all legal (no "another"), so
    `side: 'any'` with no `excludeSelf`; asserted via `P1SELECTABLEEXACT` over all three units. The two
    halves are joined by "and", not "if you do", so neither gates the other — but they compound: 1
    damage leaving a target at 1 remaining HP is then finished by the Weakness's -1 HP via
    `SWUCheckShrinkDefeats`. ⚠ The damage can DEFEAT the target, and that runs `CleanupRemovedCards`
    and re-indexes the arena — so the host is re-resolved by **UniqueID** after the damage, or the
    token strands on whichever bystander shifted into the vacated slot (that is its own section).
    When Defeated is covered on BOTH the combat and the effect-defeat path, plus a control-change
    section (owner P1 / controller P2) proving the CONTROLLER resolves it while the card still goes to
    the OWNER's discard.
  - ⚠ Harness note reconfirmed: that control-change section needs a **`P2>Drain` before P2's answer**.
    P1's action leaves P2 holding an undispatched `CUSTOM RESOLVE_TRIGGER|WhenDefeated|HMW_202`; without
    the drain the answer lands on that entry and CANCELS the trigger, presenting exactly like "the When
    Defeated never fired". Diagnosed with `TestSchemaStep`, not guessed.

- [x] **Batch 9.2 — HMW_077 Boss Nass, Otoh Gunga Boss** — 11 sections, suite 7806 -> 7817, 0 failed. — When Played/On Attack, "you may defeat a
  Shield token on a friendly Gungan unit. If you do, create a Beast token and give a Shield token to
  it." Optional COST (defeat a shield) + an "if you do" payoff; Beast = HMW_T03, created by HMW_158's
  existing path.
- [x] **Batch 9.3 — HMW_110 Emperor Palpatine, Consolidating Power** — 9 sections, suite 7817 -> 7826, 0 failed. — When Played take-control of an
  enemy non-leader unit costing 3 or less, then 2 Weakness tokens. ⚠ leader-unit exclusion must read
  the LIVE object (`IsLeaderUnit`), not printed CardType.
- [x] **Batch 9.4 — HMW_114 Breach** — 12 sections, suite 7826 -> 7838, 0 failed. — a friendly unit deals damage equal to its power to an enemy unit
  in ITS arena; if that unit has Overwhelm, excess goes to an enemy base.
