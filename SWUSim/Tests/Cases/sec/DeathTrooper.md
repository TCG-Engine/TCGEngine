# WhenPlayed_DealBothGround
#// SEC_030 Death Trooper (Ground, 3/3, Vigilance/Villainy, cost 3) — When Played: deal 2 to a friendly
#//   ground unit AND 2 to an enemy ground unit.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_030

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
