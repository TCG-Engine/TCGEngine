# NoCombatDamage
#// LAW_130 Betrayed Trust (Vigilance event, cost 2) — "Choose an enemy unit. For this phase, that unit
#// can't deal combat damage." Mark P2's SOR_046, then P1's SOR_095 attacks it: SOR_046 takes 3 but deals
#// NO counter-damage, so SOR_095 ends undamaged.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# CantDealCombatToBase
#// LAW_130 Betrayed Trust — the marked enemy unit can't deal combat damage even while ATTACKING our base.
#// P1 marks P2's AT-ST (SOR_232, 6 power); P2 then attacks P1's base with it (AT-ST exhausts, proving the
#// attack happened) but deals 0. (P1's own attack afterward is only there to resolve the exchange so the
#// board can be read mid-phase.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithP1Hand: LAW_130
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
PHASE:MAIN
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:0

---

# EnemyCantDamageDefendingUnit
#// LAW_130 Betrayed Trust — marked enemy deals no combat damage when it ATTACKS a friendly unit either.
#// P1 marks P2's AT-ST (SOR_232, 6 power); P2 attacks P1's Consular Security Force (SOR_046, 3/7): the
#// Consular takes 0, yet still deals its 3 back to AT-ST.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithP1Hand: LAW_130
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE

## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# PreventsCombatToSpaceUnit
#// LAW_130 Betrayed Trust — works in the space arena. P1 marks P2's Desperado Freighter (SHD_152, 5/6),
#// which attacks P1's HWK-290 Freighter (SHD_060, 2/5): the HWK takes 0, the freighter takes 2 counter.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithP1Hand: LAW_130
WithP1SpaceArena: SHD_060:1:0
WithP2SpaceArena: SHD_152:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
PHASE:MAIN
P1SPACEARENAUNIT:0:CARDID:SHD_060
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# DoesNotPreventNonCombatDamage
#// LAW_130 Betrayed Trust only stops COMBAT damage — a marked unit's ability damage still lands. P1 marks
#// P2's Bendu (LOF_170, 10/10, "On Attack: Deal 3 damage to each other unit"). Bendu attacks P1's base:
#// the combat hit is prevented (base = 0), but its On Attack ability still deals 3 to P1's Consular.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
WithP1Hand: LAW_130
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_170:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
PHASE:MAIN
P1BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# NoEnemyUnitsFizzle
#// LAW_130 Betrayed Trust — with no enemy units to choose, it simply resolves with no effect (it still
#// leaves hand for the discard pile; no crash / no hang).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# MultiDefender_MarkedDealsNoCounter
#// LAW_130 Betrayed Trust — the "can't deal combat damage" marker also works in a MULTI-defender attack.
#// P1 marks one of two enemy Battlefield Marines, then Darth Maul (TWI_135, 5 power) attacks BOTH. He deals
#// 5 to each (both defeated); only the UNMARKED marine deals its 3 counter, so Maul takes 3 (not 6).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1Hand: LAW_130
WithP1Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
