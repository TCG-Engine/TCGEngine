# TODO — Stored mzIDs → UniqueID migration

Status: **TODO / not started.** Read-only sweep done 2026-10-01 (after bug report #1110). Nothing below is
implemented except the When Played fix already shipped for #1110.

Line numbers are from 2026-10-01 and will drift. Grep the named function or handler before trusting them.

## The problem

A unit in play is addressed by an mzID like `myGroundArena-3`: an **index** into a per-seat arena array,
relative to the acting `$playerID`. When a unit leaves play, `CleanupRemovedCards` compacts the arena and
every later unit slides down. A newly entered unit is **appended**. So an mzID captured at time T and used
later can point at:

- an **empty slot**, which a gone-only check catches, or
- a **different unit** that refilled the slot, which a gone-only check **cannot** see.

Each arena unit also has a `UniqueID` (`SWUObjUID($obj, 0)`, `SWUFindMzByUID($uid)` → current mzID or null).

Incidents so far, all from stored mzIDs:

| Where | Report | Symptom |
|---|---|---|
| `DispatchTrigger` `case 'Shielded'` | #1091 | Maul's Shield token went to Morgan Elsbeth |
| `DispatchTrigger` `case 'Ambush'` | found 2026-09-28 | the wrong unit attacked (`sor/Piett_AmbushSurvivesIndexShift.md`) |
| `case 'WhenPlayed'` / `'HMW048Gain'` | #1110 | SEC_193 Thrawn "readied P1's Mos Espa Watermonger" (`core/WhenPlayed_SurvivesIndexShift.md`) |

The #1110 fix added `_SWUEntryTriggerMz($mzID, $extra)`. It also made `FlushTriggerBag` → `RESOLVE_TRIGGER`
forward `extraParams`, which it used to drop.

## Guiding rule

**UID for anything stored; mzID at the edges.** Keep sending mzIDs to the client and keep receiving them as
answers. Convert to a UID when storing, and back to an mzID just before use.

**Do not migrate:**

- the client/decision protocol: MZCHOOSE / MZMULTICHOOSE params and answers, `ProcessInput` command files, replays, bot answers (`BotLegalActions.php`);
- anything consumed in the same handler that produced it: indices do not move until `CleanupRemovedCards` runs.

## Findings

### 1. Trigger pipeline — the main risk, and centralised

`AddTrigger($player, $type, $cardID, $mzID, $extraParams)` (`GameLogic.php`) stores a bare mzID at **141**
call sites. Only these re-resolve the unit by identity: WhenPlayed, HMW048Gain, Shielded, Ambush,
RegroupStart, and about 21 card-specific triggers that carry a UID (in `$extra`, or riding the mzID slot as
`U{uid}` / `UID:`).

How `DispatchTrigger` uses the stored mzID: **(a)** re-resolves by UID with an identity check, **(b)**
gone-only check, **(c)** uses it raw.

| Trigger type / site | Class | Risk | Why |
|---|---|---|---|
| `OnAttack` (`CombatLogic.php` ~1346 → `OnAttackTrigger` ~4666) | c | **High** | ✅ verified: the dispatcher reads the **CardID from the slot**, so a shifted slot runs a *different card's* On Attack on a *different unit*. |
| `Support` (`GameLogic.php` ~11451) | b | **High** | ✅ verified: gone-only check, same shape as #1091. Its extra carries an attacker list, not a UID. |
| `OnDefense`, `OnDefenseFromUpgrade`, `OnAttackedFromUpgrade`, `OnAttackFromUpgrade`, `SupportOnAttack` | c | High | An earlier On Attack in the batch can defeat a lower-index unit. |
| Leader observers that fire after the attacker died: ASH_005 Luke (+#1), ASH_013 Ezra (+#1), ASH_016 Shin (+#1) (`CombatLogic.php` ~1661–1681) | c | High | Cleanup has already refilled the attacker's slot, so they heal/exclude/exhaust the unit that slid in. |
| Defender-side combat triggers: SOR_085, ASH_101, LOF_205 | b | High | Same shape JTL_120 was fixed for. |
| `RukhDefeatTrigger` (`GameLogic.php` ~10524, queued `CombatLogic.php` ~1708/1723) | b | High | Can **defeat a different enemy**. Reachable via a sibling attack-end trigger or TWI_135's two-defender attack. |
| LAW_176 Podracer (`GameLogic.php` ~15879 → ~11020) | b | High | Waits until the action ends, then readies whatever holds the slot. |
| `OnAttackEnd`, `OnAttackEndFromUpgrade`, `SupportOnAttackEnd`, `AdvantageShed` | c | Med | One trigger in the batch can defeat a friendly before the next resolves. |
| ~20 combat-hit triggers on the attacker (SOR_149, LAW_033/034/046/054/088, SEC_209/088/150/147/205, LOF_166, SOR_150, LAW_205, LOF_017, ASH_137, JTL_156) | b/c | Med | Collected before `CleanupRemovedCards`, dispatched after. |
| `WhenPlayed` as upgrade / `WhenPlayedAsUpgrade` / `OnAttached` (host mzID) | c | Med | The host can shift within an entry batch (e.g. SHD_133 Dengar damage). |
| WhenDefeated family | c (dead slot) | separate | See §3. |

Flush paths: `FlushTriggerBag` → `RESOLVE_TRIGGER` and `FlushEntryTriggerBag`/`FlushCombatTriggerBag` → EffectStack →
`RESOLVE_NEXT_TRIGGER`. Both now carry `mzID|extra`, and both split on `|`, so a payload containing `|`
corrupts.

⚠ Stale comments: three still say "FlushTriggerBag drops extraParams" (`GameLogic.php` ~11330, ~13129,
`CombatLogic.php` ~1759). Payloads riding the mzID slot still work, but the comments are now false.

### 2. Decision-queue continuations — mostly done already

About 399 `HANDLER#N|…` continuation strings. About **178 already carry UIDs**; about **63** carry a bare mzID.
Roughly 40 of those are safe: the mzID is used immediately, indexes a stable zone (hand/discard/resources),
is only an exclusion filter, or is re-resolved by UID. About **15 are risky**.

Dominant risky shape: **trigger → YESNO → act on "that unit"**, where the mzID was usually already stale
when the trigger was bagged. Fix §1 first and most of these follow.

Risky examples (unverified agent findings; confirm each with a failing test first):

- LOF_197 Aethersprite repeat: `LOF_197#0` (`GameLogic.php` ~10433/10452), `#1` (~20686, smuggle), `#2` (~11790, as upgrade)
- LOF_017 Revan (`GameLogic.php` ~10738/10762)
- `LeaderAbilities.php` ~569 ASH_005 Luke heal; ~650 ASH_013 Ezra `excludeUID` derived from a stale mz
- On Attack bonus handlers: SEC_137 Dryden Vos, HMW_041 Keeper of Skara Nal, SHD_118 Kihraxz Heavy Fighter
- `CombatLogic.php` ~2719 LAW_086 (`DEFENDER_FIRST` crosses a YESNO)
- `CardDQHandlers.php` ~1093 SOR_215 Snapshot Reflexes (host mz crosses attach decisions, then a YESNO)
- `GameLogic.php` ~7134 SOR_193 Falcon: a loop queues one YESNO per copy with mzIDs captured up front
- `GameLogic.php` ~14478 `AdvantageShed#0`; ~13412 `JTL_039#1` (choose a When Defeated ability)
- Medium: RedSquadronXWing, DeployedDroideka, LeiaOrgana_DefiantPrincess (a block-1 pair queued behind other entry triggers)
- Persisted: `SWU_THRAWN_REUSE_PENDING|cardID|mzID` is a GlobalEffect dedupe key; an index shift between add and remove leaves the guard stuck.

Queue-order note: `AddDecision` inserts after every entry with `Block <= $block`, so a block-1 pair queues
**behind** already-pending block-1 triggers that may defeat units first (the same mechanism behind
#1110's Endless Legions ordering bug).

### 3. Leave-play triggers — a different problem

A When Defeated (or capture) trigger fires for a unit that has **already left play**. `SWUFindMzByUID` skips
`removed` objects, so a UID lookup returns null by design. These need a **last-known-information record**
(cardID, UID, owner/controller, power, upgrades), not re-resolution.

Today's snapshots are keyed by **mzID string**: `$gWDPowerSnapshot` / `SWU_WDPOWER_MZ_<slot>`,
`$gCombatDefeatByMz`, `$gSec035DefeatSnapshot`, `$gAsh195DefeatSnapshot`.

Live defects (unverified):

- SEC_136 Arihnda (`cards/sec/ArihndaPryce_OnTheRoadToPower.php` ~10) reads `$selfUID` from the dead unit's (refilled) slot. IC27_024 and JTL_104 already guard with a CardID check.
- `_SWURecordDamageSource` (called from `DispatchTrigger` / `OnWhenDefeated`) attributes the source to whatever unit occupies the slot.
- THRAWN_REUSE / SHADOW_CASTER_REUSE / ENFYS_REUSE carry a raw mzID across a YESNO.

There's a real window where a unit is flagged `removed` but still holds its slot. Triggers are collected in
it, before `CleanupRemovedCards`:

- `SWUDefeatUnit` collects WhenDefeated before setting `removed`, then cleans up.
- Capture marks `removed` and fires capture triggers with no cleanup in between.

## UniqueID infrastructure — is it a sound key?

Mostly, for **arena units**.

- One per-game counter `$gUniqueIDCounter`, bumped only by `NextUniqueID()` and shared by all seats. Undo
  restores old objects but the counter isn't rolled back, so no UID is reused.
- Lifetime:

| Event | UID | Note |
|---|---|---|
| Take control (`SWUTakeControlOfUnit`) | kept | |
| Arena move (JTL_096) | kept | |
| Pilot detach | kept | the unit keeps the subcard's UID |
| Capture | dropped | the captive subcard has no UID |
| Rescue | new | |
| Bounce + replay | new | |
| Play from discard | new | |
| Leader deploy | new each time | `Leader.DeployedUniqueID` reset to 0 on return |
| Token create | new | |

  This matches CR 8.5.4 (leaving play and returning makes a new object).
- **Gaps:**
  - Only GroundArena/SpaceArena units and attached upgrades/pilots have a UID. Captives, Leader, Base,
    Hand/Deck/Discard/Resources and EffectStack entries do not.
  - `SWUFindMzByUID` searches arenas only (all seats, skips `removed`) and **can't find subcards**. It's a
    linear scan, called about 370 times.
  - Subcards are addressed `<host mz>.uN`, a raw subcard index that also shifts.
- **Latent generator bug (✅ verified):** `Schemas/SWUSim/GameSchema.txt` line 17,
  `Module: UniqueID=GroundArena,SpaceArena,UniqueID,UniqueIDCounter`, is parsed as Zone=GroundArena,
  Field=SpaceArena. The auto-stamp block is therefore never generated, and `AddGroundArena`/`AddSpaceArena`
  default `$UniqueID=0`. Harmless today, because all 23 hand-written arena adds in `SWUSim/Custom` pass
  `UniqueID:` (verified). A future generic add (e.g. `MZMove`/`MZAddZone` into an arena) would produce
  UID 0 silently. Two sentinels coexist: `-1` (field missing at parse) and `0` (Add default);
  `SWUObjUID` treats both as "no unit".
- `MZAddZone` maps "their" as `$player==1?2:1`, which is wrong above two seats. Unrelated to UIDs, noted
  in passing.

## Plan (phased, highest payoff first)

### Phase 1 — central unit resolution at trigger dispatch

- `AddTrigger` captures the UID of an **arena** mzID into a **new dedicated field**. Do **not** reuse
  `extraParams`: about 15 handlers already read `$extra[0]` as their own data (cost, count, damage).
- Both flushes serialize it as a reserved prefix, e.g. `U{uid}~<mzID>`.
- `DispatchTrigger` resolves the unit **once, before the switch**, and before the game-log / "no effect" /
  `_SWURecordDamageSource` hooks, which must see the resolved mzID. Per-type policy when the slot no longer
  holds that UID:
  - **follow the unit** (`SWUFindMzByUID`): OnAttack family, attack-end family, combat-hit, leader observers;
  - **fizzle if gone**: Shielded, Ambush, Support;
  - **keep last-known**: WhenPlayed (an ability still resolves after its unit left play);
  - **dead-slot record**: WhenDefeated family (Phase 2).
- Payloads riding the mzID slot (`U…`, `UID:…`, `cost~count`, comma lists, bare ints, `DEPLOYED_SELF`,
  `P{seat}`) must bypass resolution.
- **Frames:** `SWUFindMzByUID` returns an mzID relative to the current `$playerID`, so set the trigger
  player's frame before resolving. On Defense flips to the defender's frame.
- Then fold `_SWUEntryTriggerMz` and the Shielded/Ambush special cases into the central step.
- Risk: **medium.**

### Phase 2 — last-known-information record for leave-play triggers

- Snapshot cardID/UID/controller/power/upgrades **keyed by UID** at collection time, and pass that record
  to WhenDefeated-family dispatch.
- Re-key `gWDPowerSnapshot`, `SWU_WDPOWER_MZ_`, `gCombatDefeatByMz`, and the SEC_035/ASH_195 snapshots.
- Fixes SEC_136 Arihnda and the damage-source attribution.

### Phase 3 — continuation token helper

- Add `SWUMzToken($mz)` → `U{uid}` (falls back to the raw mz for non-arena zones) and
  `SWUMzFromToken($tok)`, reusing the existing SHD_246 / HMW_185 style.
- Convert the ~15 risky continuations card by card: LOF_197 #0–#2, Revan, Luke/Ezra, SEC_137, HMW_041,
  SHD_118, LAW_086, SOR_215, SOR_193, AdvantageShed#0, JTL_039#1, the Thrawn/Shadow Caster/Enfys reuse
  handlers and the `SWU_THRAWN_REUSE_PENDING` key.
- Risk: **low.** Each one is independent.

### Phase 4 — infrastructure hardening (optional)

- Fix the `Module: UniqueID=` line in `GameSchema.txt` (or add a guard in `*AfterAdd`) so every arena add
  stamps a UID; regenerate.
- Extend `SWUFindMzByUID` to subcards (upgrades/pilots); consider a per-request UID→mz index.

## How to do each fix (process)

- **Failing test first.** Every finding above except the ✅ ones came from an agent read. Reproduce it before
  changing code. The proven repro shape needs, in one window:
  - a unit entering or acting **after** the target's trigger was bagged;
  - a unit **below** the target leaving play, so the arena compacts;
  - a new unit **appended** into the stale slot.

  Templates: `sor/Piett_AmbushSurvivesIndexShift.md` (Support supplies the combat) and
  `core/WhenPlayed_SurvivesIndexShift.md` (HMW_043 Vader's rider kills a played unit; the When Defeated
  token refills the slot).
- Assert DAMAGE numbers / CardIDs / the game log, not "exhausted": units enter play exhausted, so it
  doesn't discriminate.
- Mutation-check every guard (revert the fix, watch the section go red, restore).
- ⚠ **RNG:** DecisionQueue and EffectStack contents are `GetAllZones` hash material, so any change to what
  is queued moves the deterministic RNG stream. Expect literal-pinned random outcomes to shift (e.g.
  `undo/RandomnessSurvivesUndo.md` moved SOR_077 → SOR_049 with #1110). Seeded bot results aren't comparable
  across such a change. Never "fix" this by renaming `GetAllZones`.
- Full suite + `[ACTION-LEDGER] BLOCKED-DOUBLE-CLOSE` diff before/after each phase.
