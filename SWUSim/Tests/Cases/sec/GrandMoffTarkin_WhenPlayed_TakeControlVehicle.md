# SEC_192 Grand Moff Tarkin (Unit, 2/6, cost 6, Cunning/Villainy, Imperial/Official)
#   "When Played: Take control of an enemy non-leader Vehicle unit. When this unit leaves play, that
#    unit's owner takes control of that unit."
# This test: the take-control on play. P1 plays Tarkin (yyk covers Cunning/Villainy → cost 6). P2's only
# Vehicle is SOR_237 (space) — the sole legal target, so the choose auto-resolves. SOR_237 moves into
# P1's space arena (controller P1, still owned by P2), and leaves P2's.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_192
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENACOUNT:0
