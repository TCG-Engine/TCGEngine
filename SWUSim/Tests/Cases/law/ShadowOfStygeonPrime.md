# CantReady_RegroupBaseDamage
#// LAW_077 Shadow of Stygeon Prime (Upgrade) — "Attach to a non-leader unit. Attached unit can't ready.
#// It gains: 'When the regroup phase starts: Deal 2 damage to your base.'" SEC_080 starts EXHAUSTED with
#// the upgrade; after a full round the ready step does NOT ready it (can't ready) and the regroup-start
#// trigger deals 2 to P1's base.

## GIVEN
CommonSetup: rrk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:LAW_077
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:2

---

# OfferPool_AttachHostIsAnyNonLeaderEitherSide
#// "Attach to a non-leader unit." names no controller, so per CR 2.e the pool spans BOTH sides and the
#// only exclusion is a deployed leader. The board carries one violator per reading: P1's deployed leader
#// and P2's deployed leader must both be OUT, while P2's ordinary units must be IN.
#// Regression guard: this card fell through to the friendly-only default pool, which was two defects at
#// once (no enemy hosts, and deployed leaders offered as hosts). With a single friendly unit on the
#// board the attach auto-resolved onto it, so nothing ever saw the pool.

## GIVEN
CommonSetup: ryk/bgw/{myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: LAW_077
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
