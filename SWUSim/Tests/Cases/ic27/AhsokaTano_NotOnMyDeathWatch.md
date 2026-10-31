# WhenPlayed_DefeatsAnEnemyUnitCostingThree
#// IC27_039 Ahsoka Tano (Ground 4/5, cost 5, [Vigilance][Heroism]) — "When Played: You may defeat a unit
#// that costs 3 or less." One optional clause. "a unit" names no side, so the pool is the whole table;
#// "costs" is the PRINTED cost (Death by Droids ruling). Fixture: CommonSetup bbw = Vigilance base +
#// Vigilance/Heroism leader, so Ahsoka costs her printed 5.
#// P2's Cloud City Wing Guard (cost 3, exactly at the threshold) is defeated; the cost-4 Security Force is
#// not a legal pick, and stays.
#// COVERAGE: offer=Offer_EveryUnitCostingThreeOrLess_WholeTable (pending SELECTABLEEXACT: both sides, both
#//           arenas, a token; cost-4 units, a deployed leader unit and Ahsoka herself excluded) ·
#//           decline=WhenPlayed_Decline_NothingIsDefeated · boundary=this section (cost 3 defeated) +
#//           Offer_… (cost 4 excluded) · control=N/A (no owner-scoped zone in the text; a defeat sends any
#//           unit to its owner's discard through the shared SWUDefeatUnit) ·
#//           reqboundary=RequestBoundary_DefeatAnsweredInAFreshRequest
#//           modes=2P,TwinSuns (unqualified "a unit" spans every seat — TwinSuns_DefeatsAFarSeatUnit),
#//                 TeamSuns (the same unqualified pool must not drop a teammate — TeamSuns_PoolIncludesYourTeammatesUnit)

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_039
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_063
P1NODECISION

---

# Offer_EveryUnitCostingThreeOrLess_WholeTable
#// The pick is left PENDING. Offered: P1's Battlefield Marine (2, ground), P1's TIE/ln Fighter (1, space),
#// P2's Cloud City Wing Guard (3) and P2's Battle Droid token (0). Excluded: both Consular Security Forces
#// (cost 4 — the boundary), P2's Munificent Frigate (5), P1's deployed Luke (a leader unit; his leader card
#// is printed 6) and Ahsoka herself (5).
#// P1 ground: Marine 0 / Security Force 1 / deployed Luke 2 / Ahsoka 3 (played last).

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirGroundArena-2
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1GROUNDARENAUNIT:3:CARDID:IC27_039

---

# WhenPlayed_Decline_NothingIsDefeated
#// "You may": declining with a legal target in play defeats nothing. Ahsoka is still played.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2DISCARDCOUNT:0
P1NODECISION

---

# NoUnitCostingThreeOrLess_NoPrompt
#// Every unit in play costs 4 or more (a Security Force on each side), so nothing is offered: no prompt,
#// Ahsoka simply enters play.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:IC27_039
P2GROUNDARENACOUNT:1

---

# WhenPlayed_DefeatsYourOwnUnit
#// "a unit" includes your own: P1's Battlefield Marine is defeated into P1's discard.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_039
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:1

---

# WhenPlayed_DefeatsATokenUnit
#// A token unit costs 0, so it qualifies. Defeating it removes it from play (a token never reaches a
#// discard pile); the cost-4 unit beside it is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:0

---

# AShieldDoesNotStopTheDefeat
#// Defeat is not damage, so a Shield token prevents nothing: the shielded Wing Guard is defeated outright.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_063

---

# RequestBoundary_DefeatAnsweredInAFreshRequest
#// WhenPlayed_DefeatsAnEnemyUnitCostingThree with a fresh request before the answer.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:1
P1NODECISION

---

# TwinSuns_DefeatsAFarSeatUnit
#// Three seats, no teams. The only unit costing 3 or less is seat 3's Wing Guard; the pool reaches it and
#// it goes to seat 3's discard. Seat 2's cost-4 unit is not offered.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-1

## EXPECT
SEATCOUNT:3
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:SOR_046
P3DISCARDCOUNT:1
P2GROUNDARENACOUNT:1

---

# TeamSuns_PoolIncludesYourTeammatesUnit
#// Team game: seat 3 is P1's teammate, seats 2 and 4 the opponents. Every seat's unit costing 3 or less is
#// offered — the teammate's Marine included — and each seat's cost-4 unit is not.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_039}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP2GroundArena: SOR_063:1:0
WithP3GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_128:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:p2GroundArena-0&p3GroundArena-1&p4GroundArena-0
