# RaidSaboteur
#// LOF_152 Focus Determines Reality — Each friendly Force unit gains Raid 1 and Saboteur for this phase.
#// Plo Koon (Force) gains both.

## GIVEN
CommonSetup: rrw/ggk/{myResources:2;handCardIds:LOF_152}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
