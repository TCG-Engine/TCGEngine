# LAW_210 Salacious Crumb (0/2 ground, Underworld/Creature, Raid 2) — "If you control Jabba the Hutt
# (as a leader or unit), this unit enters play ready." P1 controls SOR_181 Jabba the Hutt (a unit) →
# Crumb (played at index 1) enters READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_210

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_210
P1GROUNDARENAUNIT:1:READY
