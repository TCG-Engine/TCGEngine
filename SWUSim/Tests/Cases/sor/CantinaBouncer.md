# WhenPlayed_ReturnsNonLeader
#// SOR_202 Cantina Bouncer (3/5, Ground) — When Played: You may return a non-leader unit to
#// its owner's hand (either player's). P1 plays it and returns the enemy Battlefield Marine,
#// which goes back to P2's hand and leaves P2's ground arena empty.
#// COVERAGE: offer=Offer_EveryNonLeaderUnitIncludingItself_LeaderUnitExcluded (pending SELECTABLEEXACT
#//           over BOTH players' non-leader units, the just-played Bouncer included; the deployed
#//           leader unit is the excluded target) ·
#//           reqboundary=RequestBoundary_ReturnSurvivesTheAnswerArrivingInAFreshProcess ·
#//           control=ControlChange_ReturnsToTheOWNERSHandNotTheControllers (owner P2 / controller P1 —
#//           "its OWNER'S hand" must beat the controller's) · boundary pair=smallest board vs a full
#//           one: OnlyItselfOnTheBoard_ItCanReturnItself (the source is the only candidate) vs the
#//           three-candidate offer section; friendly-vs-enemy target class is
#//           ReturnsAFriendlyUnit_ToYourOwnHand vs WhenPlayed_ReturnsNonLeader ·
#//           decline=Declined_NothingIsReturned (printed "You may", answered '-').

## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_202
WithP2GroundArena: SEC_080:1:0    # enemy non-leader unit — returned to P2's hand

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# Offer_EveryNonLeaderUnitIncludingItself_LeaderUnitExcluded
#// Intended: "return a NON-LEADER unit to its owner's hand" names no controller and no exclusion for
#// the source, so the pool is every non-leader unit on the table INCLUDING the Bouncer that just
#// entered play — P1's Marine, the Bouncer itself and P2's Dark Trooper — while P2's deployed LEADER
#// unit is excluded by "non-leader". The decision is left PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: yyk/yyk/{myResources:7; theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SOR_202
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# Declined_NothingIsReturned
#// SOR_202 — the printed text is "You MAY return a non-leader unit", so the pick is declinable.
#// Answering '-' leaves every unit exactly where it was: the enemy Dark Trooper stays on the board,
#// P2's hand stays empty, and the Bouncer itself remains in play (a 3/5 on the ground).

## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_202
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_202
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2HANDCOUNT:0
P1HANDCOUNT:0

---

# ReturnsAFriendlyUnit_ToYourOwnHand
#// SOR_202 — the pool is not enemy-only. Returning P1's OWN Battlefield Marine puts it back in P1's
#// hand (its owner's), leaving only the Bouncer on P1's board; the enemy unit is untouched.

## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_202
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_202
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0

---

# ControlChange_ReturnsToTheOWNERSHandNotTheControllers
#// SOR_202 — "return it to its OWNER'S hand" is the load-bearing word. The Dark Trooper P1 CONTROLS
#// but P2 OWNS (the end state after a take-control effect) goes back to P2's hand, NOT P1's: P2's hand
#// grows to 1 while P1's stays empty. Controlled units seat before the played Bouncer, so the stolen
#// unit is myGroundArena-0.

## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_202
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_202
P1HANDCOUNT:0
P2HANDCOUNT:1
P2GROUNDARENACOUNT:0

---

# RequestBoundary_ReturnSurvivesTheAnswerArrivingInAFreshProcess
#// SOR_202 — with three legal targets the pick is a real prompt, and in production that prompt ends
#// the request: the answer arrives in a fresh process. The chosen enemy Dark Trooper still goes to
#// P2's hand, P1's Marine is untouched, and the Bouncer stays in play.

## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_202
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:SOR_202

---

# OnlyItselfOnTheBoard_ItCanReturnItself
#// SOR_202 — boundary at the smallest board: the Bouncer is the ONLY non-leader unit in play when its
#// When Played resolves, and "a non-leader unit" does not exclude the source, so it can bounce itself
#// straight back to P1's hand — the ground arena ends empty and P1 holds the Bouncer again.

## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_202

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_202
P2HANDCOUNT:0
