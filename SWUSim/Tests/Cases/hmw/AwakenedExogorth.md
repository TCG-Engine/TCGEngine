# Attacking_DefenderCountersForThreeLess
#// COVERAGE: offer=N/A (STRUCTURAL: a constant while-attacking ability) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=AttackingATwoPowerUnit_FloorsAtZero · negative=Defending_NoDebuff
#//           duration=DebuffEndsWithTheAttack · control=N/A (no owner-scoped zone)
#//           reqboundary=N/A (STRUCTURAL: applied and expired inside one attack)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//           Hidden = keyword-only half, auto-wired
#//
#// HMW_233 Awakened Exogorth — Unit (Space) 7/7, cost 7, [Cunning], Creature.
#// "Hidden. While this unit is attacking, the defending unit gets -3/-0."
#// JTL_069 Munificent Frigate is a 4/7: it dies to 7 and counters for 4 - 3 = 1.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_233:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:1

---

# AttackingATwoPowerUnit_FloorsAtZero
#// SOR_237 Alliance X-Wing (2/3): 2 - 3 floors at 0 — no damage back.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_233:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:0

---

# DebuffEndsWithTheAttack
#// A Shield soaks the Exogorth's 7, so the Frigate survives the attack at full stats: 4 power again.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_233:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:DAMAGE:1

---

# Defending_NoDebuff
#// P2's Frigate attacks the Exogorth: the Exogorth takes the full 4.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: HMW_233:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:4
P2SPACEARENACOUNT:0

---

# BaseAttack_Unaffected

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_233:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:7
P1SPACEARENAUNIT:0:DAMAGE:0
