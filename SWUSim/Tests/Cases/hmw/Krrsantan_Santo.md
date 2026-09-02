# WhenPlayed_SixResources_DealsThree
#// HMW_046 Krrsantan - Santo — Cost 4 · 4/4 · Ground · [Command][Aggression] · Underworld, Wookiee · unique
#// Text: "When Played: You may deal damage equal to the number of resources you control minus 3 to a
#//        ground unit."
#//
#// COVERAGE: offer=Offer_GroundUnitsBothSides_SpaceExcluded (SELECTABLEEXACT: both boards' ground,
#//           self included, both space arenas excluded) · decline=Decline_NoDamageAnywhere
#//           boundary=Boundary_FourResources_DealsOne + Boundary_ThreeResourcesAndACredit_AmountZero_NoPrompt
#//           control=N/A (a When Played resolves once, at play time, for the player who played the card;
#//             the amount reads that player's own resource zone and the ability never re-fires, so
#//             owner-vs-controller can never diverge for it)
#//           reqboundary=RequestBoundary_AmountSurvivesTheDecision
#//           modes=2P only ("the number of resources you control" is self-only; "a ground unit" names no
#//             controller and is built with SWUAllUnits(null,'Ground'), which spans team+opponents at any
#//             seat count) · TwinSuns=N/A (no player reference) · TeamSuns=N/A (no friendly/enemy wording)
#//
#// THE AMOUNT IS TOTAL RESOURCES CONTROLLED, NOT READY ONES. Krrsantan costs 4, so paying for him
#// exhausts 4 of the 6 — a "ready resources" reading would compute 2-3 = -1 and offer nothing at all.
#// 6 controlled - 3 = 3 damage. SOR_046 is 3/7 so it survives, keeping this section about the AMOUNT.

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P1RESCOUNT:6
P1RESAVAILABLE:2
P1NODECISION
NOEXTRAACTION

---

# WhenPlayed_EightResources_DealsFive
#// Quantity discrimination: the amount TRACKS the resource count (8-3 = 5), so a fixed number or an
#// off-by-one formula cannot pass both this and the 6-resource section above.

## GIVEN
CommonSetup: grk/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1RESCOUNT:8
P1NODECISION

---

# Boundary_FourResources_DealsOne
#// LOW BOUNDARY, and the partner of the amount-zero section below. Four resources is the fewest that can
#// pay Krrsantan's own cost outright, so this is the smallest amount a plain hand-play can produce:
#// 4 - 3 = 1. Every resource is exhausted afterwards, so a "ready resources" reading would compute -3.

## GIVEN
CommonSetup: grk/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1RESCOUNT:4
P1RESAVAILABLE:0
P1NODECISION

---

# Boundary_ThreeResourcesAndACredit_AmountZero_NoPrompt
#// THE LOAD-BEARING NEGATIVE — the "amount > 0" gate. Reaching an amount of zero needs the cost paid
#// partly with something that is NOT a resource: 3 real resources + 1 Credit token (CR 3.13 — a Credit
#// is a token, not a resource, and SWUResourceCount skips it). Krrsantan costs 4, so P1 spends the
#// Credit to pay 1 less and still controls exactly 3 resources afterwards: 3 - 3 = 0.
#// Zero damage is no effect, so the card must not raise a prompt at all (the house no-op-prompt rule
#// and the LAW_257 fizzle-only-optional family). Assert the whole board is untouched AND that no
#// decision is left dangling — a handler that offers the choice anyway reds on P1NODECISION.

## GIVEN
CommonSetup: grk/rrk/{myResources:3}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP1Credits: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:0
P1RESCOUNT:3
P1NODECISION

---

# Decline_NoDamageAnywhere
#// "You may" — an MZMAYCHOOSE decline ('-'), taken with TWO legal targets on the board so the offer is
#// genuinely pending rather than auto-resolved. Nothing takes damage on either side.

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Offer_GroundUnitsBothSides_SpaceExcluded
#// THE OFFER ITSELF. "a ground unit" names no controller and no arena beyond ground, so the pool is
#// every GROUND unit on the table — P1's own filler, Krrsantan himself (there is no "another"), and
#// both enemy ground units — while both SPACE arenas are excluded. The decision is left pending.
#// myGroundArena-0 = SEC_080 (seeded), myGroundArena-1 = Krrsantan (played, appended last).

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1
P1GROUNDARENAUNIT:1:CARDID:HMW_046

---

# DeployedLeaderUnitIsALegalTarget
#// "a ground unit" carries no "non-leader" qualifier, so a deployed leader unit is a legal target
#// (ZoneSearch's Leader Unit mapping / AnyUnitFilter). P2's Vader (SOR_010, 5/8) is deployed and takes
#// the 3 — a pool built with NonLeaderUnitFilter would not offer him at all.
#// P2 ground: SOR_046 at 0, the deployed leader appended at 1.

## GIVEN
CommonSetup: grk/rrk/{myResources:6;theirLeaderDeployed:true}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_010
P2GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# SelfTarget_KrrsantanCanDamageHimself
#// The text says "a ground unit", not "another" and not "an enemy" — and Krrsantan is in the ground
#// arena by the time his own When Played resolves, so he is his own legal target. 4 HP, 3 damage: he
#// survives, which is what makes the assertion about TARGETING rather than about a defeat.

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# FriendlyGroundUnitIsALegalTarget
#// Same unqualified-target point from the other direction: a FRIENDLY unit that is not the source.
#// SOR_046 is 3/7 so it survives the 3 and the assertion stays about the pool.
#// myGroundArena-0 = SOR_046 (seeded), myGroundArena-1 = Krrsantan.

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:HMW_046
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# LethalDamage_DefeatsTheTarget
#// The damage is ordinary damage, so it defeats a unit it kills. SEC_080 is 3/3 and takes exactly 3.

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_080
P1GROUNDARENACOUNT:1
P1NODECISION

---

# ShieldAbsorbsTheDamage
#// Interaction with the standard modifiers: this is ordinary, preventable ability damage, so a Shield
#// token absorbs the whole instance regardless of size. The unit ends undamaged and the Shield is gone.

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# RequestBoundary_AmountSurvivesTheDecision
#// The amount is computed when the offer is built and consumed when the answer comes back — which in
#// production is a DIFFERENT PROCESS. It therefore has to ride the CUSTOM decision's own Param (where
#// it is serialised with the gamestate), never an in-memory global. Identical to the 6-resource
#// positive except for the boundary inserted before the answer; a global-held amount reads 0 here.

## GIVEN
CommonSetup: grk/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1Hand: HMW_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
