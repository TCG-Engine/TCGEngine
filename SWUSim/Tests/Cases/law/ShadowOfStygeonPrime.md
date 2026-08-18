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

---

# RegroupDamageHitsTheHOSTsControllersBase
#// LAW_077 Shadow of Stygeon Prime — the granted trigger says "deal 2 damage to YOUR base", and it is text
#// the HOST gains, so "your" follows the host's controller (CR 2.e) — which is the entire point of hanging
#// this Condition on an ENEMY unit. P1 attaches it to P2's SOR_046 and at the regroup it is P2's base that
#// takes the 2, with P1's base untouched. Both decks are seeded so the regroup draws add no CR 6.1
#// empty-deck damage.

## GIVEN
CommonSetup: ryk/bgw/{myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_077
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:2
P1BASEDMG:0

---

# UpgradeRemoved_HostReadiesAgainAndTheBaseIsSafe
#// LAW_077 Shadow of Stygeon Prime — both halves ("attached unit can't ready" and the granted regroup
#// self-damage) come from the upgrade, so defeating it restores normal behaviour. P1 Confiscates the
#// Condition off its own exhausted SEC_080: at the regroup the host READIES like any other unit and no
#// damage is dealt to P1's base. CantReady_RegroupBaseDamage is the same board with the upgrade left on.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:LAW_077
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1BASEDMG:0
