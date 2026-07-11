# SHD_017 Lando Calrissian — deployed side "Action: Play a card using Smuggle. It costs 2 less. Defeat a
#   resource you own and control. Use this ability only once each round." The deployed Action has NO exhaust
#   cost (costKind 'none'), gated once-per-round. Here the deployed Lando unit uses it to Smuggle SHD_111
#   (base 3, -2 = 1) into space and stays READY afterward (no exhaust). Both picks auto-resolve (1 target
#   each), so the flow drives in the runner.

## GIVEN
CommonSetup: grk/rrk/{myLeader:SHD_017:1:1}
P1OnlyActions: true
WithP1Resources: 1:SHD_111:1
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1GROUNDARENAUNIT:0:CARDID:SHD_017
P1GROUNDARENAUNIT:0:READY
