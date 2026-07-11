# Uniqueness is PLAYER-SPECIFIC (CR 29.5): a player and their opponent may each control a copy of the
# same unique card at the same time. P2 already controls a copy of SOR_034 (Del Meeko, unique); P1
# plays their FIRST copy. P1 now controls exactly one and P2 exactly one — no violation, no prompt,
# nothing defeated. Guards that enforcement counts only the acting player's own controlled copies.

## GIVEN
CommonSetup: ybk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_034
WithP2GroundArena: SOR_034:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_034
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_034
P1DISCARDCOUNT:0
P1NODECISION
