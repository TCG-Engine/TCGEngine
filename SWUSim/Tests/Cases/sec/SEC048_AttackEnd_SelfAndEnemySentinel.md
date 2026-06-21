# SEC_048 (Ground, 7/7) — When this unit completes an attack: give this unit AND an enemy unit
#   Sentinel for this phase. SEC_048 attacks P2's base; on attack-end it gains Sentinel and grants the
#   only enemy unit (SOR_046) Sentinel too.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_048:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
