# WhenPlayed_ExpEachOther
#// SEC_119 Crucible (Ground, 5/5) — When Played/When Defeated: give an Experience token to each OTHER
#//   friendly unit. Crucible is a SPACE unit; the two ground fillers each get +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_119
P1NODECISION
