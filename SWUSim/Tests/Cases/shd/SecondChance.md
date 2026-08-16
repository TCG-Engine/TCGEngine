# FreeReplay
#// Second Chance: owner may play defeated unit for free this phase
#// ASH_259 (LEP Ratcatcher, 1/1) has Second Chance (SHD_053) attached.
#// After being defeated by SOR_095 (Battlefield Marine, 3/3), both cards go to P1's discard: SHD_053 at
#// index 0 and ASH_259 (TPF) at index 1. P1 plays ASH_259 back for free (0 resources); SHD_053 stays in
#// discard (it has no free-replay marker), leaving discard count 1.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: ASH_259:1:0   # LEP Ratcatcher 1/1
WithP1GroundArenaUpgrade: 0:SHD_053   # Second Chance on ASH_259
WithP2GroundArena: SOR_095:1:0   # Battlefield Marine 3/3
#// P1 has no resources — replay must be free (TPF) or it would fail

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

---

# UnitGetsTPFOnDefeat
#// Second Chance: defeated unit gets TPF modifier in discard
#// ASH_259 (LEP Ratcatcher, 1/1) has Second Chance (SHD_053) attached.
#// SOR_095 (Battlefield Marine, 3/3) attacks it. Power 3 >= HP 1, so ASH_259 is defeated.
#// ASH_259 power 1 < SOR_095 HP 3, so SOR_095 survives.
#// After defeat, BOTH cards go to P1's (owner's) discard: the Second Chance upgrade (SHD_053, added
#// first) and the unit ASH_259 (added second, carrying the TPF free-replay marker).

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

---

# OfferPool_AttachHostIsAnyNonLeaderEitherSide
#// "Attach to a non-leader unit." names no controller, so per CR 2.e the pool spans BOTH sides and the
#// only exclusion is a deployed leader. The board carries one violator per reading: P1's deployed leader
#// and P2's deployed leader must both be OUT, while P2's ordinary units must be IN.
#// Regression guard: this card fell through to the friendly-only default pool, which was two defects at
#// once (no enemy hosts, and deployed leaders offered as hosts). With a single friendly unit on the
#// board the attach auto-resolved onto it, so nothing ever saw the pool.

## GIVEN
CommonSetup: bbw/bgw/{myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_053
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
