# SEC_038 Condemn — the multi-copy interaction. P1's SOR_141 (1/3, Raid 2) bears TWO Condemns and
#   attacks P2's base. Each Condemn grants its own On Attack AND "loses all OTHER abilities" — so each
#   Condemn's granted On Attack is itself suppressed by the other Condemn. Result: NO disclose is offered
#   (P2NODECISION), and the unit's Raid 2 is also suppressed, so it attacks for its normal power 1.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:SEC_038
WithP1SpaceArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:1
P2NODECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
