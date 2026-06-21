# SEC_233 Beguile (event, cost 3) — Look at an opponent's hand; choose a non-leader unit that opponent
#   controls that costs 6 or less and return it to its owner's hand. P1 bounces SOR_046 (cost 4) → P2 hand.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_233

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
