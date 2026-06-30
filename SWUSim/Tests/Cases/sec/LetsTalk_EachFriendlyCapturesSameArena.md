# SEC_131 Let's Talk (Event, Command, cost 9) — each friendly unit captures an enemy non-leader unit in
#   the same arena. SOR_095 (ground) captures the lone enemy SOR_046 (ground).

## GIVEN
CommonSetup: ggk/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
