# LOF_174 Ataru Onslaught — Ready a Force unit with 4 or less power. The exhausted LOF_055 (Force, power 2)
# is readied.

## GIVEN
CommonSetup: rrw/ggk/{myResources:2;handCardIds:LOF_174}
P1OnlyActions: true
WithP1GroundArena: LOF_055:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
