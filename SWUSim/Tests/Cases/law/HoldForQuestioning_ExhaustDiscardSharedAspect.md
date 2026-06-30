# LAW_217 Hold For Questioning (Cunning,Villainy event, cost 3) — "Exhaust an enemy unit. If you do,
# look at its controller's hand and discard a card from it that shares an aspect with that unit."
# Exhaust SOR_046 (Vigilance,Heroism); the only shared-aspect card in P2's hand is SOR_237 (Heroism).

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Hand: LAW_217

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:1
P2DISCARDCOUNT:1
