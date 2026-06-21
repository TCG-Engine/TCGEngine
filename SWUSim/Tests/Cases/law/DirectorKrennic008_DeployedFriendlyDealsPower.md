# LAW_008 Director Krennic (deployed) — "When Deployed: Another friendly unit deals damage equal to its
# power to an enemy unit." Deploy Krennic (7+ resources); SEC_080 (the only other friendly, power 3)
# deals 3 to SOR_128 (3/1), defeating it.

## GIVEN
P1LeaderBase: LAW_008/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P2GROUNDARENACOUNT:0
