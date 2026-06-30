# SEC_048 (Ground, 7/7, cost 6, Vigilance/Heroism) — When Played: give this unit AND an enemy unit
#   Sentinel for this phase. P1 plays SEC_048 (on-aspect under bw leader → cost 6); the only enemy
#   unit (SOR_046) auto-resolves as the Sentinel target.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_048

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
