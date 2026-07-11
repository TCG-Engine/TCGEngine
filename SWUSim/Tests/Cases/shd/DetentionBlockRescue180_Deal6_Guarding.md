# SHD_180 Detention Block Rescue — a unit guarding a captured card takes 6 instead of 3. P1's Discerning
# Veteran (SHD_120, 4 HP) first captures SOR_128; then Detention Block Rescue hits the Veteran for 6 (it is
# guarding a captive), defeating it (3 would not have — proving the 6).

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP1Hand: SHD_180
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
