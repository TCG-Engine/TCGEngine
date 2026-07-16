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
