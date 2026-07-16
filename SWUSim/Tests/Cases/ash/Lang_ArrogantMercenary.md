# DealPowerToGround
#// ASH_123 Lang (Ground, 2/5) — Action [Exhaust]: this unit deals damage equal to his power to a ground
#// unit. Lang (power 2) uses his action and deals 2 to the enemy SEC_080.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_123:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_123
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
