# FrozenInCarbonite_CantReadyThroughRegroup
#// SHD_193 Frozen in Carbonite — "Attached unit can't ready." An exhausted host wearing SHD_193 does NOT
#// ready at the regroup ready step, while an identical exhausted unit without the upgrade does.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:SHD_193
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY

---

# FrozenInCarbonite_WhenPlayed_Exhaust
#// SHD_193 Frozen in Carbonite — "When Played: Exhaust attached unit." Played onto a ready SOR_046 → the
#// host becomes exhausted.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_193

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# OfferPool_AttachHostIsAnyNonLeaderEitherSide
#// "Attach to a non-leader unit." names no controller, so per CR 2.e the pool spans BOTH sides and the
#// only exclusion is a deployed leader. The board carries one violator per reading: P1's deployed leader
#// and P2's deployed leader must both be OUT, while P2's ordinary units must be IN.
#// Regression guard: this card fell through to the friendly-only default pool, which was two defects at
#// once (no enemy hosts, and deployed leaders offered as hosts). With a single friendly unit on the
#// board the attach auto-resolved onto it, so nothing ever saw the pool.
#// Frozen in Carbonite is pure downside ("attached unit can't ready"), so an enemy unit is its intended
#// host — being unable to reach one made the card unplayable as designed.

## GIVEN
CommonSetup: yyk/bgw/{myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_193
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
