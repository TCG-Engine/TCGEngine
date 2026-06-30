# LAW_246 The Axe Forgets (Cunning event, cost 2) — "Return a non-leader unit that costs 3 or less to
# its owner's hand." SEC_080 (cost 2) is the only unit -> auto-target -> returned to P2's hand.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_246

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1DISCARDCOUNT:1
