# TWI_211 Sly Moore (Unit 3/3, Ground, cost 3, Republic/Official) — "When Played: Take control of an enemy
# token unit and ready it." P1 takes control of P2's Battle Droid token (TWI_T01), readying it under P1.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3;handCardIds:TWI_211}
P1OnlyActions: true
WithP2GroundArena: TWI_T01:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:READY
