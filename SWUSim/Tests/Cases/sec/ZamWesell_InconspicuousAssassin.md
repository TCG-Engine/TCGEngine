# Upgraded_GainsGrit
#// SEC_029 Zam Wesell (Ground, 1/5) — While this unit is upgraded, she gains Grit. Attach SOR_120 → she
#//   is upgraded → has Grit.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_029:1:0
WithP1Hand: SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1NODECISION
