# PlusPerTrooper
#// TWI_122 Squad Support (Upgrade, Command, cost 3) — "Attach to a non-leader unit. Attached unit gains:
#// 'This unit gets +1/+1 for each Trooper unit you control.'" Host SOR_095 (Trooper) with 2 Battle Droid
#// tokens (Troopers) → 3 Troopers controlled → host gets +3/+3 → 6/6.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_122

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# OfferPool_AttachHostIsAnyNonLeaderEitherSide
#// "Attach to a non-leader unit." names no controller, so per CR 2.e the pool spans BOTH sides and the
#// only exclusion is a deployed leader. The board carries one violator per reading: P1's deployed leader
#// and P2's deployed leader must both be OUT, while P2's ordinary units must be IN.
#// Regression guard: this card fell through to the friendly-only default pool, which was two defects at
#// once (no enemy hosts, and deployed leaders offered as hosts). With a single friendly unit on the
#// board the attach auto-resolved onto it, so nothing ever saw the pool.
#// Squad Support was the subtler case: it HAD its own case in the attach switch, but that case only
#// filtered leaders out of the friendly-only default pool, so it looked handled while still omitting
#// every enemy host.

## GIVEN
CommonSetup: ggw/bgw/{myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: TWI_122
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
