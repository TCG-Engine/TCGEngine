# SOR_162 Disabling Fang Fighter — MultiUnit
# P2 has two units each with one Experience token — multiple upgrade targets.
# After YES the player picks a unit (MZCHOOSE). Chosen unit has exactly one
# upgrade so it is auto-defeated without a second prompt.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 1:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
