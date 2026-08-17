# FriendlyUnitDefeated_HealsOneFromTheAttachedBase
#// HMW_113 Sinister War Memorial (Command/Villainy, Fortification, 2-cost Upgrade) —
#// "Fortify (Attach this to your base, not a unit.) Attached base gains 'When a friendly unit is
#// defeated: Heal 1 damage from this base.'"
#// COVERAGE: offer=N/A (the heal is non-interactive and untargeted — "THIS base") ·
#//           negative=EnemyUnitDefeated_NoHeal (the "friendly" half) + NoMemorial_NoHeal (the gate) ·
#//           boundary pair=one friendly defeat heals 1 vs two defeats heal 2 (per-unit, not per-event) ·
#//           control=OpponentsMemorial_HealsTHEIRBase (each base heals only for its own controller's
#//           friendly losses) · reqboundary=N/A (the heal resolves inline in the defeat collection with
#//           no intervening decision) · decline=N/A (no "you may") ·
#//           clamp=HealClampsAtZero (a base at 0 damage cannot go negative)
#// P1's base starts at 3 damage. P1's SOR_095 attacks a bigger enemy and dies; the Memorial on P1's base
#// heals 1, so P1's base ends at 2.

## GIVEN
CommonSetup: gbk/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_113
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2

---

# NoMemorial_NoHeal
#// HMW_113 — the gate. Identical board with NO Memorial on the base: the same friendly unit dies and
#// P1's base stays at 3. Without this the heal could be coming from anywhere.

## GIVEN
CommonSetup: gbk/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:3

---

# EnemyUnitDefeated_NoHeal
#// HMW_113 — "a FRIENDLY unit". P1's attacker kills a weak enemy and survives, so the only defeat is an
#// ENEMY one: P1's base must not heal. This is the half a scan of all defeated units would fail.

## GIVEN
CommonSetup: gbk/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_113
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:3

---

# TwoFriendlyUnitsDefeated_HealsTwice
#// HMW_113 — the ability is per DEFEATED UNIT, not per event. SOR_043 Superlaser Blast defeats all units
#// at once; P1 loses TWO, so the Memorial heals 2 (base 4 -> 2).
#// ⚠ Also exercises the simultaneous-defeat path, where observers are judged against the pre-effect
#// board — the same machinery the HK-47 / Rogue One family uses.

## GIVEN
CommonSetup: bbk/bgw/{myBaseDamage:4;myResources:8}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_113
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SOR_043

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2

---

# OpponentsMemorial_HealsTHEIRBase
#// HMW_113 — "this base" is the base the Memorial is attached to, and "friendly" is relative to that
#// base's controller. Here the Memorial is on P2's base while P1's base is also damaged: P1's friendly
#// unit dying must heal NEITHER base (it is not friendly to P2), and P2 losing a unit heals only P2's.
#// P2's SOR_128 attacks P1's bigger unit and dies, so the only defeat is P2's own.

## GIVEN
CommonSetup: gbk/gbk/{myBaseDamage:3;theirBaseDamage:3}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2BaseUpgrade: HMW_113
WithP2GroundArena: SOR_128:1:0
WithP1GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1BASEDMG:3

---

# HealClampsAtZero
#// HMW_113 — a base with NO damage cannot heal below zero. Same flow as the first section with the base
#// undamaged; it must stay at 0 rather than going negative.

## GIVEN
CommonSetup: gbk/bgw/{myBaseDamage:0}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_113
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0

---

# TwoMemorials_HealTwicePerDefeat
#// HMW_113 is NOT unique, so two copies may sit on the same base and each grants its own ability —
#// one friendly defeat heals 2 (base 4 -> 2). Guards against a boolean "does the base have one?" gate,
#// which would heal only 1 no matter how many are attached.

## GIVEN
CommonSetup: gbk/bgw/{myBaseDamage:4}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_113
WithP1BaseUpgrade: HMW_113
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2
