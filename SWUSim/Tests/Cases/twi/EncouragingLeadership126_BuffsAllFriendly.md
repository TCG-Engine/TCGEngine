# TWI_126 Encouraging Leadership (Event, cost 3, Command) — "Give each friendly unit +1/+1 for this
# phase." Both friendly units become +1/+1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_126}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
