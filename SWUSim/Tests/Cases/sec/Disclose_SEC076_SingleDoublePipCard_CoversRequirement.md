# DISCLOSE (CR §38.1) — a single double-pip card covers a multi-icon requirement
# SEC_076 "You may disclose VigilanceVigilance ... If you do, defeat a damaged non-leader unit."
#
# Proves the requirement is a MULTISET of aspect ICONS, not distinct aspects: one card with
# two Vigilance pips (SEC_054, aspect "Vigilance,Vigilance") alone satisfies "VigilanceVigilance".
#
# P1 hand after playing the event: just SEC_054. Disclose it → its two Vigilance icons cover
# the requirement → defeat the lone damaged enemy (auto-resolves). SEC_054 stays in hand.

## GIVEN
CommonSetup: bbk/grw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_076
WithP1Hand: SEC_054
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION
