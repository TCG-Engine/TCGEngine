# LAW_132 The Tree Remembers (Vigilance event, cost 4) — "An enemy unit loses all abilities for this
# phase. If it costs 3 or less, defeat it." SEC_080 (cost 2) -> defeated.

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
