# RaidPerDamagedEnemy
#// SEC_171 Punishing One (Ground, 3/5, Aggression) — "Raid 1 for each damaged enemy unit" + On Attack:
#//   may deal 1 to a unit. With two damaged enemies → Raid 2; decline the On Attack ping → attacks the
#//   base for 3 + 2 = 5.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_171:1:0
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:5
P1NODECISION

---

# WhenPlayed_Deal1
#// SEC_171 Punishing One — When Played: you may deal 1 to a unit.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# OnAttack_Deal1ToUnit
#// SEC_171 Punishing One — On Attack: may deal 1 to a unit. Attacks the base and pings the enemy Wampa
#//   (SOR_164) for 1.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1SpaceArena: SOR_066:1:0
WithP1SpaceArena: SEC_171:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# RaidRecomputesAfterOnAttackPing
#// SEC_171 Punishing One — Raid 1 per damaged enemy is recomputed after the On Attack ping. One enemy is
#//   pre-damaged (A-Wing SOR_141); pinging a fresh Wampa makes 2 damaged enemies → Raid 2 → base 3 + 2 = 5.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1SpaceArena: SEC_171:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_141:1:2

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P1NODECISION

---

# RaidDoubledByMarchionRo
#// SEC_171 Punishing One — Marchion Ro (LOF_186) doubles each friendly unit's Raid. With two pre-damaged
#//   enemies (Wampa + A-Wing), Raid 2 is doubled to Raid 4; pinging the already-damaged Wampa adds none →
#//   base 3 + 4 = 7.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: LOF_186:1:0
WithP1SpaceArena: SEC_171:1:0
WithP2GroundArena: SOR_164:1:2
WithP2SpaceArena: SOR_141:1:2

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P1NODECISION
