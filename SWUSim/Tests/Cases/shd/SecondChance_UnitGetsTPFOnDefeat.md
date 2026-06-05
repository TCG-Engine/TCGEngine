# Second Chance: defeated unit gets TPF modifier in discard
# ASH_259 (LEP Ratcatcher, 1/1) has Second Chance (SHD_053) attached.
# SOR_095 (Battlefield Marine, 3/3) attacks it. Power 3 >= HP 1, so ASH_259 is defeated.
# ASH_259 power 1 < SOR_095 HP 3, so SOR_095 survives.
# After defeat, BOTH cards go to P1's (owner's) discard: the Second Chance upgrade (SHD_053, added
# first) and the unit ASH_259 (added second, carrying the TPF free-replay marker).

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: ASH_259:1:0   # LEP Ratcatcher 1/1
WithP1GroundArenaUpgrade: 0:SHD_053   # Second Chance on ASH_259
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine 3/3

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SHD_053
P1DISCARDUNIT:1:CARDID:ASH_259
P1DISCARDUNIT:1:MODIFIER:TPF
