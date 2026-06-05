# SOR_097 Admiral Ackbar — the damage equals the number of units you control in the CHOSEN unit's
# arena, NOT your total units. P1 has 3 ground units (incl. Ackbar) but only 1 space unit. Targeting
# an enemy SPACE unit deals 1 (the friendly SPACE count), proving it counts the target's arena.
# JTL_069 (4/7) survives at DAMAGE:1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP1Hand: SOR_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:1
