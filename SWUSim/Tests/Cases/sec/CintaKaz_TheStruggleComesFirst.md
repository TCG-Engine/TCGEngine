# WhenPlayed_MayAttack
#// SEC_172 Cinta Kaz (Ground, 5/5, cost 6) — When Played: you may attack with a unit. P1 plays SEC_172
#//   and attacks with the ready SEC_041 → P2's base takes 1.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_172

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:1
