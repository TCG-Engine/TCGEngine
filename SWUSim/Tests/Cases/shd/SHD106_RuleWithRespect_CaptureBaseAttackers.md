# SHD_106 Rule with Respect — "A friendly unit captures each enemy non-leader unit that attacked your
# base this phase." P1 passes; P2's SHD_095 attacks P1's base (marking it a base-attacker); P1 then plays
# SHD_106 and has SOR_046 capture SHD_095.

## GIVEN
CommonSetup: ggw/ggk/{myResources:4}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_106
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
