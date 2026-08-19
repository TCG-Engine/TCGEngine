# NoBaseUpgrades_PrintedStats_NoRestore
#// HMW_066 Carrion Spike - Harbinger of Tyranny (Vigilance/Villainy, Imperial/Vehicle/Capital Ship,
#// cost 5, 3/5 SPACE, unique) — "Shielded / For each upgrade on your base, this unit gets +1/+0 and
#// gains Restore 1."
#// COVERAGE: offer=N/A (two continuous passives, no target pick and no decision of any kind) ·
#//           negative=this section (0 upgrades) + EnemyBaseUpgradesDoNotCount ·
#//           boundary=OneBaseUpgrade vs TwoBaseUpgrades_ScalesBOTH — a PAIR, because one upgrade cannot
#//           tell "+1 per upgrade" from "+1 flat", and it is also what pins the Restore reading ·
#//           control=ControlChange_ReadsTheNEWControllersBase · reqboundary=RequestBoundary_PassiveSurvives ·
#//           decline=N/A (no cost, no "you may")
#// ⚠ THE JUDGEMENT CALL — "For each upgrade on your base, this unit gets +1/+0 AND GAINS RESTORE 1."
#// The leading "For each" scopes the WHOLE predicate, so both halves scale: 2 upgrades = +2/+0 AND
#// Restore 2, not +2/+0 and a flat Restore 1. Had they meant the flat reading the text would read "gets
#// +1/+0 for each upgrade on your base and gains Restore 1". TwoBaseUpgrades_ScalesBoth and
#// RestoreScalesAndActuallyHeals are the sections that discriminate the two; if the flat reading is the
#// intended one, those are the only two that change.
#// ⚠ "YOUR base" is controller-scoped — deliberately unlike HMW_074 Yord Fandar's unqualified "a base",
#// which spans both. EnemyBaseUpgradesDoNotCount is the guard.
#// Baseline: no upgrades anywhere, so Carrion Spike sits at its printed 3/5 with no Restore.

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>Drain

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_066
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:NOTKEYWORD:Restore

---

# OneBaseUpgrade_PlusOnePowerAndRestoreOne
#// HMW_066 — one Fortify upgrade on the controller's base: +1/+0 (4/5) and Restore 1.

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>Drain

## EXPECT
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# TwoBaseUpgrades_ScalesBoth
#// HMW_066 — the boundary partner, and the judgement call made observable. TWO upgrades give +2/+0 (5/5).
#// One upgrade alone cannot distinguish "+1 per upgrade" from "+1 flat"; this can.
#// (The Restore half of the same scaling is asserted behaviourally in RestoreScalesAndActuallyHeals —
#// HASKEYWORD is a boolean and cannot show Restore 2 vs Restore 1.)

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1BaseUpgrade: HMW_081
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>Drain

## EXPECT
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# HpIsNeverBuffed
#// HMW_066 — "+1/+0" is POWER ONLY. With three upgrades the power climbs to 6 and the HP stays at the
#// printed 5. A "+1/+1" slip passes every power assertion above and fails only here.

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1BaseUpgrade: HMW_081
WithP1BaseUpgrade: HMW_147
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>Drain

## EXPECT
P1SPACEARENAUNIT:0:POWER:6
P1SPACEARENAUNIT:0:HP:5

---

# EnemyBaseUpgradesDoNotCount
#// HMW_066 — "YOUR base". The OPPONENT has two Fortify upgrades and the controller has none, so Carrion
#// Spike stays at its printed 3/5 with no Restore. A scan that counted upgrades on "a base" (the HMW_074
#// Yord Fandar reading) would give it +2/+0 here.

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_095
WithP2BaseUpgrade: HMW_081
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>Drain

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:NOTKEYWORD:Restore

---

# RestoreScalesAndActuallyHeals
#// HMW_066 — the granted Restore WORKING, and the amount pinned. Restore heals the attacker's own base
#// when it attacks, so with TWO base upgrades a 5-damage base is healed by 2 → 3. Under the flat reading
#// (Restore 1 regardless) it would read 4, so this is the behavioural half of the judgement call.
#// It also proves the grant reaches the real keyword reader rather than only the HASKEYWORD assertion.

## GIVEN
CommonSetup: bbk/rrk/{myBaseDamage:5}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1BaseUpgrade: HMW_081
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:5

---

# ShieldedOnEntry
#// HMW_066 — the OTHER printed clause. Shielded is registry-derived, so this is a regression guard: it
#// fails loudly if a regen ever stops detecting it. It has to be PLAYED (a seeded unit never runs the
#// entry hook), which is why every other section here seeds instead — keeping the stat numbers clean.
#// Cost 5, and the base+leader cover Vigilance and Villainy, so it pays printed.

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_066

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_066
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:POWER:3

---

# ControlChange_ReadsTheNEWControllersBase
#// HMW_066 — "your base" follows whoever CONTROLS the ship, not who owns it. P1 owns it, P2 controls it,
#// and the upgrades are on P2's base — so it is buffed for P2. An owner-scoped read finds P1's clean base
#// and denies the bonus entirely.

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_095
WithP2BaseUpgrade: HMW_081
WithP2SpaceArenaControlled: HMW_066:1

## WHEN
- P1>Drain

## EXPECT
P2SPACEARENAUNIT:0:CARDID:HMW_066
P2SPACEARENAUNIT:0:POWER:5
P2SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# RequestBoundary_PassiveSurvives
#// HMW_066 — the request-boundary cell. The card raises no decision, so the boundary goes between two
#// player ACTIONS: production starts a fresh process there, and a passive computed from a serialized
#// zone (the base's Subcards) must still read the same afterwards. A value memoised into a transient
#// global at first read would go stale.

## GIVEN
CommonSetup: bbk/rrk/{myBaseDamage:5}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_095
WithP1BaseUpgrade: HMW_081
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>Drain
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:POWER:5
P1BASEDMG:3
P2BASEDMG:5
