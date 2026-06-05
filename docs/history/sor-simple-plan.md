# SOR — Simple-Tier Implementation Plan

Execution roadmap for every card classified **Simple** in [sor-implement.md](sor-implement.md). Card text, stats, and per-card implementation notes live in that file — this plan only sequences the work and tells you when to invoke the skill.

## How to use this plan

- **Each batch = one `swusim-implement-card` invocation.** The skill looks up card text, writes **all DSL tests first**, then **stops for review before touching implementation code**. Implement only after the tests are approved.
- **Batches are grouped by shared pattern** so one mechanic is reinforced per cycle. Do them top-to-bottom: each phase is easier-or-equal to the next and de-risks the harness before the harder triggers.
- **Per batch, the cycle is:** invoke skill → review tests → approve → implement → run regression → **retro** → check off below.
- **End-of-batch retro:** after the regression is green, read `docs/swu-impl-retro.txt` and answer its prompt (lessons learned that could improve the `swusim-implement-card` skill). **Report to the user only — make no changes** unless approved. (memory: `feedback-batch-retro`)
- **Test runner (never host PHP):** `curl http://localhost:3400/TCGEngine/zzRegressionSWUSim.php`
- **Never commit.** The user commits manually.
- **Already Done — skip if encountered:** SOR_010, SOR_106, SOR_124, SOR_162, SOR_216, SOR_226, SOR_244, SOR_251 (these appear in Simple lists in sor-implement.md but are already implemented).

> **When ready to start a batch, invoke:** `swusim-implement-card` with the listed card IDs.

---

## Phase 0 — Vanilla (no implementation, no tests)

Vanilla cards have a **blank text box** (no keywords, no abilities — just stats/traits/aspects). They are **auto-implemented by the generated card dictionaries** and **never get unit tests** (see memory `feedback-vanilla-no-tests`). A vanilla upgrade's printed +power/+HP flows through the existing `ObjectCurrentPower`/`ObjectCurrentHP` upgrade loop.

**Do NOT invoke `swusim-implement-card` for these.** The only action is an optional sanity-check that each dictionary entry exists with correct stats.

- [ ] Confirm dictionary entries — Units: `SOR_046, SOR_095, SOR_128, SOR_210, SOR_225, SOR_237, SOR_247` · Upgrades (stat-only): `SOR_120, SOR_069`

---

## Phase 1 — Pure keywords (keywords already wired)

**Real starting point.** Pure single-keyword cards whose keyword is already implemented are **auto-wired by the generator and need no tests/code** — same as vanilla (memory: `feedback-pure-keyword-no-tests`). For each: confirm `$textData` is keyword-only, confirm registry membership in `GeneratedKeywordCode.php`, confirm a generic keyword test exists → mark Done. **Do NOT invoke `swusim-implement-card`.**

- [x] **Batch 1.1 — Single innate keyword (DONE, no-op):** `SOR_032` (Grit), `SOR_165` (Grit), `SOR_044` (Restore 1), `SOR_063` (Sentinel), `SOR_066` (Sentinel), `SOR_098` (Sentinel), `SOR_229` (Sentinel), `SOR_250` (Sentinel), `SOR_064` (Shielded), `SOR_207` (Shielded) — all registry-wired + generically tested; marked Done in sor-implement.md.
- [x] **Batch 1.2 — Single innate keyword cont. (DONE, no-op):** `SOR_205` (Saboteur), `SOR_239` (Saboteur), `SOR_213` (Ambush), `SOR_232` (Overwhelm), `SOR_164` (Overwhelm), `SOR_141` (Raid 2), `SOR_157` (Raid 2) — all registry-wired + generically tested; marked Done in sor-implement.md.
- [x] **Batch 1.3 — Keyword-granting / multi-keyword (DONE):** all 5 already implemented. `SOR_117` (Ambush+Overwhelm), `SOR_194` (Saboteur+Raid 2) — multi-keyword no-ops (auto-wired + generically tested). `SOR_166` (grants Saboteur) — covered by existing `core/UpgradeSaboteur_Grant.md`. `SOR_057` (grants Sentinel), `SOR_070` (grants Restore 2) — hand-wired in `KeywordEffects.php` conditional switches; added guard tests `sor/Protector_GrantsSentinel.md` + `sor/Devotion_GrantsRestore.md` (no prior coverage of Sentinel-/Restore-via-upgrade). Regression: 230 pass / 0 fail.
- [x] **Batch 1.4 — Keyword + rider (DONE):** `SOR_101` (Ambush + WhenPlayed return ≤2-cost unit from discard) — new `SWUReturnFromDiscardToHand` + `$whenPlayedAbilities["SOR_101:0"]` + `SOR101_RETURN_DISCARD` handler. `SOR_248` (Raid 1 + "costs 1 less if you control a Trooper") — **established the M9 `PlayCostModifier` foundation**: new `SWUComputePlayCost` single-source-of-truth used by both `CanAffordActivationReserve` and `ActivateCard`, with a `$playCostModifiers` registry (SHD_182 Bravado migrated off its inline hack into it). New test infra: `discardCardIds`/`theirDiscardCardIds` GIVEN opt + `WithCardInDiscardForPlayer` builder. Tests: `VolunteerSoldier_TrooperDiscount`, `VolunteerSoldier_NoTrooper_FullCost`, `RogueSquadronSkirmisher_ReturnFromDiscard`. Regression: 233 pass / 0 fail.

**✅ Phase 1 complete** — all keyword batches done.

---

## Phase 2 — Conditional passive buffs (Krennic/Wedge pattern)

Field-presence passives in `ObjectCurrentPower` / `ObjectCurrentHP`: loop the arena, switch on card ID, dedupe with a seen-set.

- [x] **Batch 2.1 — Self/other-unit stat passives (DONE):** `SOR_081` (self +2/+0 @≥6 res), `SOR_161` (self +2/+0 while initiative), `SOR_230`/`SOR_242` (other friendly Imperial/Rebel +1/+1 via new shared `SWUTraitCommanderBonus`, self-excluded by UniqueID), `SOR_082` (+0/+1 while controlling Palpatine SOR_006). All added to the **live** `ObjectCurrentPower`/`ObjectCurrentHP` (lines 157/203 — NOT the dead GA-fallback at 10555). `SOR_113` already implemented (Sentinel-via-≥6-res); added behavioral guard. 9 tests. Regression: 242 pass / 0 fail.
- [x] **Batch 2.2 — Keyword-grant passives + attack-conditional (DONE):** `SOR_079` already done (Ambush-grant mechanism covered by Wedge test). `SOR_211` (Cunning→Sentinel), `SOR_159` (Aggression→self Raid 2) already done — added guard tests. `SOR_144` (other Heroism→Raid 1) — added to `GetConditionalKeyword_Raid_Value` (same loop as SOR_012). `SOR_130` (+2/+0 **and** Overwhelm while attacking a damaged unit) — combat-resolver work in `SWUCombatDamage` (the old "resolver re-checks" comment was a lie; nothing was wired). `SOR_034` (opponent events cost 1 more) — extended the M9 framework with a **field-presence** `$playCostFieldModifiers` registry (source-keyed) consulted in `SWUComputePlayCost`'s field loop. 6 tests. Regression: 248 pass / 0 fail.

_(Batch 2.3 — SOR_080, the WhenPlayed multi-target Experience passive — still pending.)_
- [x] **Batch 2.3 — WhenPlayed multi-target Experience passive (DONE):** `SOR_080` (give Exp to each of up to 3 Trooper units) — first SWU use of **MZMULTICHOOSE** (`"min|max|specs"` param, `&`-delimited result); `$whenPlayedAbilities["SOR_080:0"]` collects Trooper units → MZMULTICHOOSE 0–3 → `SOR080_GIVE_EXP` gives an Experience token to each. 2 tests + UI smoke test (multichoose renders as a live field selection). Regression: 250 pass / 0 fail.

**✅ Phase 2 complete.**

---

## Phase 3 — Single-effect events/units (existing damage/defeat/heal/debuff primitives)

One MZCHOOSE/PASSPARAMETER + one primitive. Uses `DealDamage`, `SWUDefeatUnit`, `HealUnit`/`OnHealBase`, `SWUApplyPhaseDebuff`, `OnExhaustCard`, `DoDrawCard`.

- [x] **Batch 3.1 — Damage/defeat (DONE):** `SOR_172` already implemented (added test). New: `SOR_077` (defeat ≤5 remaining HP) + `SOR_078` (defeat non-leader) via new generic **`DEFEAT_UNIT`** handler; `SOR_173` Bombing Run (arena YESNO + UID-safe AOE loop — `SOR173_BOMB` re-resolves mzID per UID since dealing damage shifts indices); `SOR_127` Strike True (two-step friendly-dealer→enemy, power encoded across handlers); `SOR_025` Tarkintown base epic (deal 3 to damaged non-leader, `SOR025_DEAL` + SWUAfterAction). 6 tests. Regression: 256 pass / 0 fail.
- [x] **Batch 3.2 — Buff/debuff/heal/draw/exhaust (DONE):** `SOR_111` (WhenPlayed draw), `SOR_073` (give Shield via new universal `GIVE_SHIELD`), `SOR_154` (each friendly gains Raid 2 — `AddTurnEffect RAID_2`, infra already reads it), `SOR_028` (base epic −4/−0 via `APPLY_PHASE_DEBUFF`). **Two new mechanics:** `SOR_140` (lose Sentinel) — added a **generic source-preserving `NO_<keyword>` suppression** to the keyword generator (`Data/ProcessKeywordsSWU.php` boolean template, regenerated `GeneratedKeywordCode.php`; `strpos` match keeps the `SOR_140-NO_SENTINEL` source for Active Effects). `SOR_074` (heal a unit **or base**) — confirmed bases ARE valid MZCHOOSE targets via `myBase-0`/`theirBase-0` (`MZZoneCount`/`GetZone` support them); handler branches `OnHealBase`/`OnHealUnit`. 6 tests. Regression: 262 pass / 0 fail. **Known UI gap:** base slots don't get `FieldSelectionMetadata` highlight when targeted — engine works, but a human clicking a base target needs the slot made selectable (GameLayout follow-up).
- [x] **Batch 3.3 — Multi-step single effects (DONE):** `SOR_220` Surprise Strike (CardID-named handler: `SWUApplyPhaseBuff +3/+0` then `BeginSWUAttack`). `SOR_234` Maximum Firepower (4-step Imperial chain `SOR234_TARGET`→`DEAL1`→`DEAL2`; new reusable `SWUFindMzByUID` re-resolves the shared target by UniqueID across the damage). `SOR_252` Restock (MZMULTICHOOSE discard cards → `_topDeckPutRemainingToBottom`, owner-routed). 3 tests. Regression: 265 pass / 0 fail.

**✅ Phase 3 complete.** (Next: Phase 4 — trigger abilities, Batches 4.1–4.6.)

---

## Phase 4 — Trigger abilities (WhenPlayed / OnAttack / WhenDefeated)

The bulk of the tier. Split into themed chunks; each chunk is one skill invocation cycle.

- [x] **Batch 4.1 — Damage-to-unit/base triggers (DONE):** `SOR_033` (2 friendly + 2 enemy ground, chained), `SOR_038` Count Dooku (Shielded + may defeat ≤4 remaining HP — note: 5/4, so he's always his own valid target → the defeat is always a real MZCHOOSE, never auto-resolve), `SOR_090` (may deal damage = `SWUResourceCount`), `SOR_132` (may deal 3 to space), `SOR_134` (WhenPlayed/WhenDefeated shared closure: 2 to enemy base + 2 to enemy unit), `SOR_176` (may reveal an event then deal 1), `SOR_121` upgrade (Vehicle attach + granted OnAttack via `SWU_CURRENT_DEFENDER`, deal 2 in defender's arena), `SOR_151` Karabast (event: friendly.Damage+1 to enemy, chained). 8 tests. Regression: 273 pass / 0 fail.

**Test gotcha logged:** a unit with Shielded/Ambush **and** a WhenPlayed has TWO entry triggers → the player first orders them via a MZCHOOSE answered with `EffectStack-N` (not the WhenPlayed's own choice).
- [x] **Batch 4.2 — Experience-token granters (5/6 DONE):** new universal **`GIVE_EXPERIENCE|N`** handler. `SOR_037` (AOE: Exp to each damaged friendly), `SOR_231`/`SOR_241` (2 Exp to another Imperial/Rebel via shared `$sorExpToTrait`, self-excluded by UID), `SOR_108` (WhenDefeated may: Exp to a unit), `SOR_049` (WhenDefeated: 2 Exp to another friendly + Force→draw). 5 tests (incl. 2 WhenDefeated combat). Regression: 278 pass / 0 fail.
  - ✅ **SOR_094 DONE — unit activated-ability foundation BUILT.** New `SWUUnitAction`/`SWUUnitActionAffordable`/`SWUGetUnitActionProvider` (mirror the leader path; dispatch from the unit's own `$unitAbilities` **or** an attached upgrade's — ready for TWI_120). Input: new `myGroundArena`/`mySpaceArena` case in CustomInput → `SWUUnitAction` (self-drains the DQ). UI: `unitActions` in `SWUComputeActionsData` + click-the-glowing-unit (`.unit-action` glow, capture-phase `handleUnitActionClick` → `…!CustomInput!Activate`). DSL: `UseUnitAbility:mzID`. Verified end-to-end via real `ProcessInput`. **Unblocks the other action cards:** SHD_028 (exhaust + deal-1 cost), SOR_093/SOR_177 (play-from-hand −1 effect), TWI_120 (upgrade-granted), SOR_110 (defeat-self + new attack mechanic).
- [x] **Batch 4.3 — Shield / heal triggers:** `SOR_050` (give Shield to another Spectre), `SOR_053` (upgrade: if host is Luke, heal all + Shield), `SOR_059` (OnAttack: heal 2 from another unit), `SOR_060` (WhenDefeated: Shield to Vigilance unit), `SOR_068` (if another Vigilance unit, heal 4 from base)
  - **Invoke `swusim-implement-card`** with the 5 IDs above. ✅ Done (7 tests, 295 passing).
- [x] **Batch 4.4 — Exhaust / ready / bounce:** `SOR_039` (exhaust all ground units), `SOR_086` (give a unit Sentinel this phase), `SOR_099` (bounce friendly non-leader ground + draw), `SOR_178` (if another Cunning unit, exhaust enemy ≤4-cost), `SOR_202` (return non-leader unit), `SOR_208` (if leader unit, exhaust non-leader), `SOR_209` (Raid 1 + return friendly non-leader), `SOR_214` (upgrade grants OnAttack ready-resource), `SOR_221` (choose arena, exhaust all units in it)
  - **Invoke `swusim-implement-card`** with the 9 IDs above. ✅ Done (12 tests, 307 passing). Added infra: HASKEYWORD/NOTKEYWORD test assertions, OPTIONCHOOSE decision type (+ OptionChooseUI.js), phase keyword-grant clear.
- [x] **Batch 4.5 — Attack-with riders + OnAttack utility:** `SOR_206` (OnAttack: pay 2, draw), `SOR_218` (exhaust enemy + Shield to friendly ≤3-cost), `SOR_227` (attack with unit; Imperial +2/+0), `SOR_240` (attack with unit; Rebel +2/+0)
  - **Invoke `swusim-implement-card`** with: SOR_206, SOR_218, SOR_227, SOR_240. ✅ Done (6 tests, 313 passing). Also fixed a latent bug: "+X/+0 for this attack" (SOR_220) was a lingering phase buff → now a one-shot `SWUAddAttackPowerBonus` consumed in combat.
- [x] **Batch 4.6 — Draw / resource / discard / conditional-upgrade:** `SOR_083` (WhenDefeated: put self into play as resource), `SOR_126` (put event into play as resource), `SOR_136` (upgrade: if host is Vader, deal 4 to ground unit), `SOR_147` (discard hand, draw 3), `SOR_163` (WhenDefeated: if initiative, draw 2), `SOR_171` (choose a player, they draw 2)
  - **Invoke `swusim-implement-card`** with the 6 IDs above. ✅ Done (9 tests, 322 passing).

---

## Phase 5 — Deck search / play events (DoTopDeckSearch / DoTopDeckPlay)

Infrastructure proven by SOR_084/087/125.

- [x] **Batch 5.1:** `SOR_096` (search top 5 for a Rebel, pick 1), `SOR_123` (search top 5 for a unit, pick 1), `SOR_104` (search top 10 for ≤3 units, combined cost ≤7, play free)
  - **Invoke `swusim-implement-card`** with: SOR_096, SOR_123, SOR_104. ✅ Done (3 tests, 325 passing). Added `$maxCount` cap to `DoTopDeckPlay` (+ frontend `cost:N:M`).

---

## Phase 6 — Leaders now Simple

Leader-side action + deployed OnAttack; infra proven session 30 (Bo-Katan). Each leader card carries both its leader-side ability and its deployed text — implement together.

- [x] **Batch 6.1:** `SOR_007` (leader Action: Exp to Imperial; deployed OnAttack: Exp to another Imperial), `SOR_012` (IG-88 — leader-side attack handled separately; deployed passive: each other friendly gains Raid 1)
  - ⚠ `SOR_012`'s **leader-side action** ("attack with a unit; +1/+0 if you control more units") is **Medium**, not Simple — only the **deployed Raid-1 passive** is Simple here. Scope this batch to the deployed passive + SOR_007.
  - **Invoke `swusim-implement-card`** with: SOR_007, SOR_012. ✅ Done (4 tests, 329 passing). SOR_012's Raid-1 passive was already implemented; SOR_007 leader-action + deployed OnAttack added. **SOR Simple plan COMPLETE — all 6 phases done.**

---

## Progress tracking

Total to implement: ~87 cards across 6 phases / 15 batches (excludes the 9 vanilla cards in Phase 0, which need no work, and the 8 already-Done cards listed at top). Update sor-implement.md's **Already Done** line as each batch lands, and re-run the regression curl after every batch.
