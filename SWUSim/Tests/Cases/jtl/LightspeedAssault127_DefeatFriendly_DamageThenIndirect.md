# JTL_127 Lightspeed Assault — "Defeat a friendly space unit and deal damage equal to its power to an
# enemy space unit. If you do, deal indirect damage equal to the enemy unit's power to its controller."
# P1 defeats JTL_069 (power 4), dealing 4 to the enemy SOR_225 (2/1) which dies; then 2 indirect (its
# power) goes to P2, who now controls no units, so it auto-resolves onto P2's base. SOR_237 (the other
# friendly space unit) is the non-chosen option and survives.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENACOUNT:0
P2BASEDMG:2
