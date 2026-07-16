# AttachedUnitLosesAbilities
#// SHD_072 (upgrade: "Attach to a non-leader unit. Attached unit loses its current abilities and can't
#// gain abilities.") A debuff played onto the ENEMY SOR_063 Cloud City Wing Guard (Sentinel): the host
#// carries the upgrade (UPGRADECOUNT:1) but no longer counts as having Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP1Hand: SHD_072

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
