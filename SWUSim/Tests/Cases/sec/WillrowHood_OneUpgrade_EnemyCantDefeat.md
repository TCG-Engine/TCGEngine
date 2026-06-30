# SEC_061 Willrow Hood — "While this unit has exactly 1 friendly upgrade on it, that upgrade can't be
#   defeated or returned to hand by enemy card abilities." P2's Willrow bears exactly 1 friendly upgrade
#   (SOR_120). P1 plays Confiscate ("Defeat an upgrade") targeting it — but the enemy defeat is blocked,
#   so the upgrade survives (Confiscate is spent for nothing).

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0
