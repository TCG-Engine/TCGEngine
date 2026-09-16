# OpponentPlaysAnEvent_ResourcesTheTopCard
#// COVERAGE: offer=N/A (STRUCTURAL: mandatory, nothing chosen) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=N/A (STRUCTURAL: no number) · negative=OpponentPlaysAUnit_NoReaction and
#//           YouPlayAnEvent_NoReaction · quantity=TwoSaws_TwoResources · empty=EmptyDeck_NothingHappens
#//           control=N/A (the reactor resources from ITS controller's deck) · reqboundary=N/A (STRUCTURAL: no decision)
#//           modes=2P,TwinSuns (text says "an opponent") — TwinSuns_ReactorOnSeatThree
#//
#// HMW_119 Saw Gerrera — Unit (Ground) 3/6, cost 4, [Command][Heroism], Rebel.
#// "When an opponent plays an event: Resource the top card of your deck."
#// SOR_251 Confiscate is a neutral 1-cost event that fizzles with no upgrade in play.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2;theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: HMW_119:1:0
WithP1Deck: [SOR_095 SEC_080]
WithP2Hand: SOR_251

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1RESCOUNT:3
P1DECKCOUNT:1
P1RESAVAILABLE:2

---

# OpponentPlaysAUnit_NoReaction

## GIVEN
CommonSetup: ggw/ggw/{myResources:2;theirResources:2}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: HMW_119:1:0
WithP1Deck: [SOR_095 SEC_080]
WithP2Hand: SOR_095

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P1RESCOUNT:2
P1DECKCOUNT:2

---

# YouPlayAnEvent_NoReaction

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_119:1:0
WithP1Deck: [SOR_095 SEC_080]
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:2
P1DECKCOUNT:2

---

# TwoSaws_TwoResources

## GIVEN
CommonSetup: ggw/ggw/{myResources:2;theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [HMW_119:1:0 HMW_119:1:0]
WithP1Deck: [SOR_095 SEC_080 SOR_128]
WithP2Hand: SOR_251

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1RESCOUNT:4
P1DECKCOUNT:1

---

# EmptyDeck_NothingHappens

## GIVEN
CommonSetup: ggw/ggw/{myResources:2;theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: HMW_119:1:0
WithP2Hand: SOR_251

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1RESCOUNT:2
P1BASEDMG:0

---

# TwinSuns_ReactorOnSeatThree
#// P1 plays the event; Saw sits on seat 3, an opponent of P1. Seat 3 resources; P1's own deck is untouched.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SEC_080]
WithP3GroundArena: HMW_119:1:0
WithP3Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3RESCOUNT:1
P3DECKCOUNT:1
P1DECKCOUNT:2
