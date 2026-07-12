# TWI_067 The Zillo Beast (Unit 10/10, Ground, cost 9) — "When Played: Give each enemy ground unit
# -5/-0 for this phase." Both enemy ground units (SEC_080 3/3, SOR_046 3/7) drop to 0 power.

## GIVEN
CommonSetup: bbw/grw/{myResources:9;handCardIds:TWI_067}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:1:POWER:0
