# TWI_194 Ahsoka Tano (Unit 3/4, Ground) — "While you control fewer units than an opponent (including
# this unit), this unit gains Ambush." Guard: P1 has only Ahsoka (1) vs P2's 2 units → HASKEYWORD Ambush.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_194:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
