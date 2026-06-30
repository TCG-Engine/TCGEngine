# Second Chance: owner may play defeated unit for free this phase
# ASH_259 (LEP Ratcatcher, 1/1) has Second Chance (SHD_053) attached.
# After being defeated by SOR_095 (Battlefield Marine, 3/3), both cards go to P1's discard: SHD_053 at
# index 0 and ASH_259 (TPF) at index 1. P1 plays ASH_259 back for free (0 resources); SHD_053 stays in
# discard (it has no free-replay marker), leaving discard count 1.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: ASH_259:1:0   # LEP Ratcatcher 1/1
WithP1GroundArenaUpgrade: 0:SHD_053   # Second Chance on ASH_259
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine 3/3
# P1 has no resources — replay must be free (TPF) or it would fail

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_259
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_053
P1RESAVAILABLE:0
