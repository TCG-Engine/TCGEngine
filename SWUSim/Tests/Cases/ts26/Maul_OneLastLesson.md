# WhenPlayedAttackWithAnother
#// TS26_030 Maul (Unit 5/4, cost 4) — Sentinel. When Played: you may attack with another unit. Playing
#// Maul lets SEC_080 attack the enemy base for 3.
## GIVEN
CommonSetup: ryk/rrk/{myResources:4;handCardIds:TS26_030}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
