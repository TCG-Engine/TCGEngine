# WhenDefeatedDealTwo
#// ASH_153 Green Leader (Space, 3/1) — When Defeated: you may deal 2 damage to a unit. Green Leader
#// attacks SOR_225 (2/1) and both die; its WhenDefeated deals 2 to the enemy SEC_080.
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_153:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
