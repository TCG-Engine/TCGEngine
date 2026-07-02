# Leader Flip-Side Audit — Gaps

> **✅ RESOLVED 2026-06-27 — ALL 23 gaps below are now implemented + tested** (TDD, via
> `swusim-implement-card`). Regression 2303 passing, 0 failing. The Method C leader-sweep is CLEAN
> across ASH/LOF/SEC (no structural deployed-side gaps). Each fix shipped with a deploy→exercise
> test under `Tests/Cases/{ash,lof,sec}/`. Two adjacent fixes were needed: the `HasOnAttackEndAbility`
> stub was hand-extended for ASH_001/LOF_016 (generator didn't detect their phrasing), and the
> ASH_001 ramp condition was corrected (it gated on the attach trigger-count, which is 0 for a
> vanilla upgrade). The sections below are retained as the historical gap list.

**Date:** 2026-06-27
**Scope:** All 110 leaders in the implemented sets (SOR, JTL, LOF, SEC, IBH, LAW, ASH).
**Goal:** verify every leader's *deployed* (Leader Unit) side is implemented — On Attack,
When Deployed, attack-end, passives, keywords, deployed Actions, pilot/upgrade behavior,
and the reactive triggers.

> **Why this audit exists:** ASH_011 Cad Bane's deployed "On Attack: deal 1 damage" was
> found silently no-op'ing — `HasOnAttackAbility('ASH_011')` returned true but no
> `$onAttackAbilities["ASH_011:0"]` handler existed. The dispatch does
> `if (!isset($onAttackAbilities[$key])) continue;`, so the ability was skipped with no
> error. The ASH set-validation had dismissed these as "spurious OnAttack stubs / false
> positives" — but they are **real**. This sweep re-checks every leader for that class of gap.

## Method (reproducible)

For each leader CardID with `typeData==='Leader'` in an implemented set:

1. **Detector vs handler** — for each generated `Has<Trigger>Ability(cardID)` detector
   (in `GeneratedCode/GeneratedAbilityStubs.php`), if it returns `true`, confirm the matching
   handler array (`$onAttackAbilities`, `$whenPlayedAbilities`, `$whenDefeatedAbilities`,
   `$onAttackEndAbilities`, `$onDefenseAbilities`, …) contains a `"{cardID}:0"` key.
2. **Deployed Action** — if the deployed text has `Action [...]:`, confirm `$unitAbilities[cardID]`
   is registered (deployed leader Actions route through `SWUUnitAction` →
   `SWUGetUnitActionProvider`, which needs a `$unitAbilities` entry; it does **not** fall back
   to `$leaderAbilities`).
3. **Passive / reactive** — grep the deployed text's stat/keyword/reactive phrasing against the
   stat chokepoints (`ObjectCurrentPower`/`ObjectCurrentHP`), `KeywordEffects.php`, combat hooks,
   and the `DispatchTrigger` reactive cases.

Bootstrap mirrors `TestSchemaSetup.php` (engine + `Custom/GameLogic.php`, which pulls all
`Custom/*` handler files and populates the `$*Abilities` arrays).

## Verified CLEAN (no gaps)

- **Keywords** on deployed sides (Restore N, Grit, Sentinel, Shielded, Saboteur, Raid N,
  Overwhelm, Hidden, Ambush) — generator-detected from `deployTextData`, auto-wired.
- **Reactive triggers** — SEC_002 (dealt-damage-survives), SEC_008 (play from resources),
  SEC_013 (friendly defeated while attacking), SEC_016 (reveal/discard), SEC_017 (combat dmg
  to base), LAW_003 (play Heroism), LAW_007 (Bounty Hunter attack ends), LAW_014 (reuse On
  Attack), JTL_002 (reuse When Defeated), SOR_002 (enemy defeated → heal), SOR_013 (damage enemy
  base → draw), ASH_005/013/016/017 (friendly attack-ends / play-or-create). All implemented.
- **Pilot leaders / deploy-as-upgrade / attached-unit-gains-On-Attack** — JTL_003/006/009/015/017/018 wired
  (`onAttached`, `whenPlayedAsUpgrade`, keyword grants, etc.). ⚠ **CORRECTION 2026-07-01:** JTL_001/008/011/012
  were WRONGLY listed here — their pilot-grant **On Attack** (dispatched via `OnAttackFromUpgradeTrigger` →
  `$onAttackAbilities["<CID>:0"]`) had **no handler**, so the granted On Attack silently no-op'd (the ASH_011
  class; only the keyword-grant halves — Grit / "can't be defeated" — worked). Method C caught it; fixed
  2026-07-01 (registered the four `$onAttackAbilities[:0]` handlers, each guarded to fire only via the pilot
  path since these leaders' deployed-UNIT side lacks the On Attack; JTL_008 also extended `SWUComputePlayCost`
  so its "next Pilot costs 1 less" covers the unit-play path too, not just Piloting/attach). Regression 2326/0;
  Method C now clean. Unlike these, **JTL_018's** On Attack IS on its own deployed-unit side too, so it correctly
  fires on both paths with no guard.
- **Poe JTL_013 single hop** — `$unitAbilities["JTL_013"]` + `SWUGetPoe013AttachVehicles`.
- **Deployed passives that ARE handled** — SOR_001, SOR_003, SOR_004, SOR_008, SOR_012, SOR_018,
  SEC_009/010/011/012/018, LAW_009, JTL_005 (cost modifier), SEC_011.

---

## GAPS (23 missing abilities across 21 leaders)

### A. Deployed "On Attack" not implemented — 8

`HasOnAttackAbility` true, no `$onAttackAbilities["{CID}:0"]`. The deployed unit attacks and the
ability silently does nothing. Fix: add the handler (pattern: ASH_011, now fixed; or SOR_010).

| CardID | Name | Deployed On Attack |
|--------|------|--------------------|
| ASH_003 | Baylan Skoll | You may give a friendly unit +2/+2 and Sentinel for this phase if it's the only non-leader unit you control in its arena. |
| ASH_004 | Grand Admiral Thrawn | If you control more units than the defending player, you may defeat a non-leader unit they control. *(Restore 2 keyword auto-OK.)* |
| ASH_006 | Sabine Wren | The next unit you play this phase gains Shielded for this phase. |
| ASH_009 | Ahsoka Tano | You may give a unit with less power than this unit +2/+0 for this phase. *(Support keyword separate.)* |
| ASH_010 | Bo-Katan Kryze | If you control a unit in each arena, create a Mandalorian token. *(Also has a passive gap — see D.)* |
| ASH_012 | Vane | You may defeat a friendly upgrade. If you do, deal 2 damage to the defending unit or a base. |
| ASH_014 | The Mandalorian | If you have the initiative, you may draw a card. *(Support keyword separate.)* |
| ASH_015 | Emperor Palpatine | You may choose another exhausted friendly unit. If you do, give an Advantage token to it for each other friendly unit. |

### B. Deployed "When Deployed" not implemented — 2

`HasWhenPlayedAbility` true, no `$whenPlayedAbilities["{CID}:0"]`.

| CardID | Name | When Deployed |
|--------|------|---------------|
| LOF_001 | Kylo Ren | Play any number of upgrades from your discard pile on this unit (one at a time, paying their costs). *(Sentinel keyword auto-OK.)* |
| LOF_012 | Rey | You may discard your hand. If you do, draw 2 cards. |

### C. Deployed attack-end / completes-attack not wired — 2

Own-unit attack-end trigger; needs `$onAttackEndAbilities["{CID}:0"]`.

| CardID | Name | Trigger |
|--------|------|---------|
| ASH_001 | The Armorer | **When Attack Ends:** You may play an upgrade from your resources on a friendly unit. If you do, resource the top card of your deck. |
| LOF_016 | Qui-Gon Jinn | **When this unit completes an attack (and survives):** return a friendly non-leader unit to hand, then play a cheaper non-Villainy unit free. *(The effect handlers `LOF_016#0/#1` already exist for the front Action — only the deployed trigger is unwired. Mind the "and survives" gate.)* |

### D. Deployed passive not implemented — 6

No field-presence handling in the stat chokepoints / keyword-grant code.

| CardID | Name | Passive |
|--------|------|---------|
| ASH_007 | Grand Admiral Sloane | Each other friendly unit gains Overwhelm and Sentinel. *(keyword grant — GetConditionalKeyword/collection)* |
| ASH_010 | Bo-Katan Kryze | Other friendly Mandalorian units get +1/+0. *(stat passive — ObjectCurrentPower; also has On Attack gap — see A)* |
| ASH_018 | Grogu | While another friendly unit is defending, it gets +1/+0. While another friendly unit is attacking, the defending unit gets -1/-0. *(combat stat hooks, cf. SOR_018)* |
| LOF_004 | Kanan Jarrus | While you control another Creature or Spectre unit, this unit gets +2/+2. *(self conditional)* |
| LOF_007 | Avar Kriss | While the Force is with you, this unit gets +4/+0 and gains Overwhelm. *(self conditional buff + keyword grant)* |
| LOF_011 | Kit Fisto | This unit gets +1/+0 for each other friendly Jedi unit. *(self scaling)* |

### E. Deployed leader-unit "Action" not wired — 4

Deployed text has `Action [...]:` but no `$unitAbilities[CID]` entry, so
`SWUGetUnitActionProvider` returns `''` and the Action is **unreachable** (no UI glow, no
execution). Fix: register `$unitAbilities[CID]` + `$unitActionCostKind[CID]` + (if any)
`$unitActionResourceCosts[CID]` — pattern: LAW_003 / LAW_015 / JTL_013.

| CardID | Name | Deployed Action | Cost note |
|--------|------|-----------------|-----------|
| ASH_002 | Fennec Shand | Play a unit from your hand. It enters play ready. | `[1 resource, exhaust a friendly unit]` — costKind exhaust-a-*friendly* (not self) + 1 resource. *(Saboteur keyword auto-OK.)* |
| SEC_007 | Dryden Vos | Play a unit from your hand (paying its cost). It gains Ambush for this phase. | `[discard a card from your hand]` — discard-cost kind. *(Overwhelm keyword auto-OK.)* |
| LOF_013 | Barriss Offee | Play an event from your hand. It costs 1 resource less. | **`[use the Force]` only — NO exhaust.** |
| LOF_018 | Anakin Skywalker | Play a Villainy non-unit card from your hand, ignoring its aspect penalties. | **`[use the Force]` only — NO exhaust.** |

> **Force-action exhaust nuance (LOF_013, LOF_018):** the *front* side costs `[Exhaust, use the
> Force]`, but the *deployed* side costs `[use the Force]` only. So the deployed leader unit must
> **not exhaust** when using it and must remain usable **while exhausted** (cost is losing the
> Force token, gated like `$leaderActionForceCost`). Use a non-exhaust costKind (cf. LAW_003's
> `'none'`) plus a Force-token payment, not the default `'exhaust'`.

### F. Minor / uncertain — 1  ✅ RESOLVED 2026-07-01

| CardID | Name | Item |
|--------|------|------|
| SOR_016 | Grand Admiral Thrawn | ~~"When the action phase starts: Look at the top card of each player's deck." No `ActionPhaseStart` hook found.~~ **DONE** — the APS peek is implemented in `ActionPhaseStart` (`GameLogic.php`, fires whenever SOR_016 is in the leader zone, so it covers BOTH the undeployed front side and the deployed unit side; logs a private `REVEAL` visible only to Thrawn's controller = "look at"). Both sides verified to ≥96% 2026-07-01: front (APS peek + `Action [1 resource, exhaust]` reveal/exhaust + Epic deploy) and deployed (APS peek + `On Attack` may-reveal/exhaust) each have a real handler AND a test (`GrandAdmiralThrawn_APS_*`, `_LeaderAction_*` ×5, `_OnAttack_Yes/No`, `_Deploy`). Also fixed a latent `$playerID` non-restore in the APS block that could mis-resolve the adjacent SOR_017 Han-Solo `myResources` MZCHOOSE when Thrawn is in play. Regression 2320/0. |

---

## Summary

| Category | Count | Leaders |
|----------|-------|---------|
| A. Deployed On Attack | 8 | ASH_003/004/006/009/010/012/014/015 |
| B. When Deployed | 2 | LOF_001, LOF_012 |
| C. Attack-end / completes | 2 | ASH_001, LOF_016 |
| D. Passive | 6 | ASH_007/010/018, LOF_004/007/011 |
| E. Deployed Action | 4 | ASH_002, SEC_007, LOF_013, LOF_018 |
| F. Minor/uncertain | 1 | SOR_016 |
| **Total** | **23** | **21 distinct leaders** (ASH_010 in two categories) |

The concentration is **ASH (12 leaders)** and **LOF (8 leaders)** — both sets pair an Epic-deploy +
front Action with a distinct deployed-side trigger that was systematically skipped. SOR/JTL/SEC/LAW
deployed sides are otherwise clean. Each fix should ship with a deployed-side test (cf.
`Tests/Cases/ash/CadBane_PingLeaders.md`, which proved the ASH_011 gap).
