# SHD_254 Bounty Guild Initiate — without another friendly Bounty Hunter unit, the gate fails and there is
# no offer. The enemy SOR_046 is untouched and no decision is pending.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_254
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
