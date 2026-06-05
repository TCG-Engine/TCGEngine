# JTL_043 No Glory, Only Results vs a ship piloted by JTL_103 Chewbacca — the Chewbacca pilot grants the
# host "can't be defeated by enemy card abilities." No Glory takes control of the ship FIRST, so the
# defeat is friendly and the immunity does not apply: the ship is defeated. Both the ship AND the
# Chewbacca pilot upgrade land in their owner (P2)'s discard (discard +2) — defeated cards always go to
# their own owner's discard, never the controller's.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
