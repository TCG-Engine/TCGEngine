# DISCLOSE (CR §38.1) — SEC_076 Charged with Murder (Event, cost 4, Vigilance)
#   "You may disclose VigilanceVigilance (reveal cards from your hand with these aspect icons
#    among them). If you do, defeat a damaged non-leader unit."
# Proves the multi-icon requirement satisfied by TWO single-Vigilance cards collectively
# (CR 38.1 "the revealed cards collectively have at least the aspect icons specified"), and
# that disclose works from an event's effect.
#
# P1 hand after playing the event: SEC_059 + SEC_062 (both Vigilance). Disclose both → covers
# VigilanceVigilance → defeat a damaged non-leader unit. The lone damaged enemy (Battlefield
# Marine, 1 damage) is the only legal target, so the mandatory defeat auto-resolves on it.
# Both disclosed cards remain in hand; the event goes to the discard pile.

## GIVEN
CommonSetup: bbk/grw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_076
WithP1Hand: SEC_059
WithP1Hand: SEC_062
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION
