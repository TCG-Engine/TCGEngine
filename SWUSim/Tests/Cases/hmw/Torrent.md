# GivesOneWeaknessToken_WhenYourBaseIsNotNaboo
#// COVERAGE: offer=TheOfferSpansBOTHSidesAndIncludesADeployedLeader (P1SELECTABLEEXACT over a 4-unit
#//           board) · decline=N/A (no "you may" — the give is mandatory; the no-target fizzle is
#//           NoUnitsInPlay_CleanFizzle) · boundary=OneTokenIsNotLethal / NabooDoubleISLethal (the 1-vs-2
#//           count is the threshold, tested as a pair on one board)
#//           control=N/A (an Event — fixed caster, and "you control a Naboo base" is read for the
#//           CASTER; the seat-scoping negative is OpponentHasTheNabooBase_StillOnlyOne)
#//           reqboundary=N/A (both tokens are attached inside one continuation — nothing is written
#//           before the target decision and read behind it)
#//           modes=2P only (no player reference, no friendly/enemy wording — "a unit" is unqualified)
#//
#// HMW_100 Torrent — Event, cost 2, Vigilance, Disaster.
#//   "Give a Weakness token to a unit. If you control a Naboo base, give 2 Weakness tokens to that
#//    unit instead."
#// ⚠ PREVIEW SET — no official rulings for HMW. Read from the CR plus released twins: the Weakness token
#// is HMW_T02 (-1/-1), the base condition mirrors HMW_240 Sandstorm's "while you control a Tatooine
#// base" (`_SWUControlsBaseWithTrait`), and "give 2" mirrors HMW_110 Emperor Palpatine, which attaches
#// two tokens with back-to-back DoGiveTokenUpgrade calls.
#//
#// THE BASE CASE. P1's base is HMW_019 Dune Sea — TATOOINE, not Naboo — so one token: SOR_046 goes
#// 3/7 → 2/6.
#// ⚠ Dune Sea is deliberately the same ASPECT (Vigilance) as the Naboo base used below, so the ONLY
#// difference between this section and the Naboo one is the base's TRAIT. A negative built on a
#// different-aspect base would also change the event's cost and stop being a clean pair.

## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_019}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6

---

# NabooBase_GivesTWOInstead_NOTThree
#// THE CONDITIONAL, and the word that matters is "INSTEAD": two tokens TOTAL, not one plus two.
#// Same board, base swapped to HMW_020 Great Grass Plains (Naboo, also Vigilance): 3/7 → 1/5.
#// A reading of 0/4 with UPGRADECOUNT 3 would be the additive misread — which is why the count is
#// asserted alongside the stats rather than the stats alone.
## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_020}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# OpponentHasTheNabooBase_StillOnlyOne
#// SEAT SCOPING. "If YOU control a Naboo base" is the caster's base, not any base on the table. P2 holds
#// the Naboo base and P1 holds Tatooine, so the answer is ONE token — a check written as "a Naboo base
#// is in play" reads two here and passes every other section in this file.
## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_019;theirBase:HMW_020}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2

---

# FriendlyUnitIsALegalTarget
#// "A unit" carries NO controller qualifier, so it reaches YOUR OWN units as well as the enemy's — and
#// on this card that is a real (if unattractive) play, not a technicality. Every other section aims at
#// an enemy, so a friendly-only or enemy-only pool would pass all of them.
#// One unit on the board, so the mandatory choose auto-resolves onto it.
## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_019}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# TheOfferSpansBOTHSidesAndIncludesADeployedLeader
#// THE OFFER CELL — answering a target proves the branch, never the pool. The decision is left PENDING
#// and the pool itself asserted.
#// The board is deliberately four units across both players and both arenas, including P1's DEPLOYED
#// leader: "a unit" says neither "non-leader" nor an arena, so a deployed leader unit and a space unit
#// are both legal. A pool that quietly restricted to non-leaders, to the enemy, or to one arena would
#// still satisfy every behavioural section in this file.
#// ⚠ A deployed leader is appended LAST to the ground arena, so P1's ground reads SOR_095 then Iden.
## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_019;myLeader:SOR_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&theirGroundArena-0

---

# OneTokenIsNotLethal
#// BOUNDARY, first half. Weakness is -1/-1 and the -1 HP has no state-based defeat of its own, so the
#// give must run a shrink sweep. SOR_095 (3/3) seeded with 1 damage becomes 2/2 with one token — 1
#// remaining HP, alive.
## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_019}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
WithP2GroundArena: SOR_095:1:1
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# NabooDoubleISLethal_WhereSingleWasNot
#// BOUNDARY, second half — the SAME board with only the base swapped to Naboo. Two tokens make it 1/1
#// against 1 damage: zero remaining, so the shrink sweep defeats it and the arena empties.
#// This pair is what pins the count at exactly 2: at 1 token the unit lives, at 3 it would also die, so
#// neither section alone fixes the number — together with the UPGRADECOUNT assertions above they do.
## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_020}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
WithP2GroundArena: SOR_095:1:1
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0

---

# NoUnitsInPlay_CleanFizzle
#// NO VALID TARGET. The event still costs its 2 resources and still reaches the discard — the cost buys
#// the ability, not the effect resolving — but nothing is offered and no decision is left dangling.
## GIVEN
CommonSetup: bbw/bbw/{myBase:HMW_020}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_100]
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2NODECISION
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:2
