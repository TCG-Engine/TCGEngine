# DefeatedTokenUnit_CeasesNotDiscarded
#// CORE RULE — a TOKEN ceases to exist when it leaves play; it never enters a discard pile.
#// P1 plays SOR_078 Vanquish ("Defeat a non-leader unit") on P2's JTL_T01 TIE Fighter token. The token
#// leaves the arena, and P2's discard stays EMPTY — only P1's own spent Vanquish event is discarded.
#// This is load-bearing beyond bookkeeping: discard COUNT and CONTENTS gate real effects ("play a unit
#// from your discard", discard-size conditions, SOR_091-style return-from-discard), so a token sitting
#// in a discard pile is both an illegal state and a replayable card that should not exist.
#//
#// REGRESSION GUARD (engine, 2026-08-02): every leave-play path funnels through SWUAddToDiscard, which
#// added the token like any other card. The guard now lives in that one function, so it covers unit
#// defeat, the host-subcard strip on a defeated unit, and forced discards alike — and it fires NO
#// "when discarded" observer, because a token that ceases was not discarded.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;handCardIds:SOR_078}
P1OnlyActions: true
WithP2GroundArena: JTL_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# DefeatedUnitsTokenUpgrades_CeaseWhileRealUpgradesDiscard
#// CORE RULE — when a unit is defeated its upgrades leave play too, and the SAME token rule applies to
#// them: TOKEN upgrades (Shield SOR_T02, Experience SOR_T01) cease, while REAL upgrade cards go to their
#// owner's discard. P1's SOR_046 carries one real upgrade (SOR_069) plus a Shield and an Experience
#// token; P2 defeats the host with SOR_078 Vanquish.
#// P1's discard therefore holds exactly TWO cards — the host SOR_046 and the real upgrade SOR_069 — and
#// neither token appears. Proves the guard is applied on the host-subcard strip path, not just the
#// defeated unit itself.

## GIVEN
CommonSetup: bbw/bbw/{theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Hand: SOR_078
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
