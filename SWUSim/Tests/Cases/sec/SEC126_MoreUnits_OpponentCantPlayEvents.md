# SEC_126 Trade Route Taxation (event, cost 2) — Choose an opponent. If you control more units than
#   that opponent, they can't play events for this phase. P1 (2 units) > P2 (1 unit) → P2's event
#   SEC_246 is blocked and stays in hand.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_126
WithP2Hand: SEC_246
WithP2Resources: 4

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2
