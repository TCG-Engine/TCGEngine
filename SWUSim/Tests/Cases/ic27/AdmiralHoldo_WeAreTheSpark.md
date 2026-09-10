# HoldoInPlay_YouDrawThreeAtRegroup_TheOpponentTwo
#// IC27_038 Admiral Holdo (We Are The Spark) — 5-cost 3/7 [Vigilance][Heroism] Resistance/Official, unique.
#// "Draw 1 more card during the regroup phase."
#// The regroup draw (CR 5.4.b: each player draws 2) becomes 3 for Holdo's CONTROLLER, while she is in play
#// with her abilities, when the draw step happens. It is read at the draw step, never snapshotted.
#// ⚠ PREVIEW ASSUMPTION (no official ruling exists for a preview card): the extra card is part of the SAME
#// regroup draw instruction, not a second one — so a deck-out is ONE damage event of 3 × undrawn
#// (the 2026-09-07 deck-out ruling). EmptyDeck_TheThreeUndrawnCardsAreONE_NineDamageEvent pins it.
#// The round: P1>Pass ends the action phase (P2 auto-passes under P1OnlyActions) → regroup draw → each
#// player's "resource a card" prompt, declined. Passing a resource keeps the hand, so HANDCOUNT is the draw.
#//
#// COVERAGE: offer=N/A (nothing is chosen — the draw count is fixed by the board)
#//           decline=N/A (not optional: "draw 1 more card", no "may")
#//           boundary=DeckOfThree_DrawsAll_NoDamage vs DeckOfTwo_DrawsTwoAndTakesThree
#//           control=StolenHoldo_TheThiefDrawsThree_TheOwnerTwo
#//           reqboundary=PlayedThisRound_AcrossTheBoundary_StillDrawsThree
#//           modes=2P,TwinSuns (the draw loop visits every live seat — TwinSuns_HoldoOnSeat3_OnlySeat3DrawsThree)
#//                 TeamSuns=N/A (no friendly/enemy wording; "you" is the controller alone)

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:2
P2HANDCOUNT:2
P2DECKCOUNT:3
P1BASEDMG:0
P2BASEDMG:0

---

# NoHoldo_BothDrawTwo
#// The control: the same board without her — the printed regroup draw of 2 each.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:3
P2HANDCOUNT:2

---

# OpponentsHoldo_OnlyTheyDrawThree
#// "Draw" is Holdo's controller's draw — an opponent's Holdo gives YOU nothing.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP2GroundArena: IC27_038:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:3
P2DECKCOUNT:2

---

# OnlyTheRegroupDraw_AnActionPhaseDrawIsStillOne
#// "During the regroup phase" — an action-phase draw is untouched. Patrolling V-Wing (SOR_111, a Command
#// SPACE unit, under a Command base) draws exactly 1 with Holdo in play.

## GIVEN
CommonSetup: gbw/rrk/{myResources:2;myhandCardIds:SOR_111}
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:4

---

# DeckOfThree_DrawsAll_NoDamage
#// The boundary pair, first half: exactly three cards left — all three are drawn and nothing is dealt.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:0
P1BASEDMG:0

---

# DeckOfTwo_DrawsTwoAndTakesThree
#// Second half: two cards left — both are drawn, and the one card that could not be drawn deals 3.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:0
P1BASEDMG:3

---

# EmptyDeck_TheThreeUndrawnCardsAreONE_NineDamageEvent
#// The preview assumption, made observable. ASH_070 At Attin Safety Droid: "If your base would be dealt
#// more than 4 damage, prevent all but 4 of that damage." With an empty deck the three undrawn cards are
#// ONE instruction → ONE event of 9 → capped to 4. Were Holdo's card a SEPARATE draw instruction it would
#// be 6 (capped to 4) plus its own 3, for 7.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0
WithP1GroundArena: ASH_070:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:0
P1BASEDMG:4

---

# EmptyDeck_WithoutTheDroid_TheFullNineLands
#// The baseline for the section above: the same single event lands in full — 3 × 3 undrawn.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:0
P1BASEDMG:9

---

# Imprisoned_LosesTheAbility_NoExtraDraw
#// SHD_072 Imprisoned: "Attached unit loses its current abilities and can't gain abilities." A blanked
#// Holdo still sits in play but gives no extra card.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:3

---

# BlankedForTheActionPhaseOnly_StillDrawsThree
#// A "for this phase" blank (SOR_138 Force Lightning's marker) ends with the action phase, so by the regroup
#// draw Holdo has her ability back.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_038:1:0:SOR_138
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:2

---

# StolenHoldo_TheThiefDrawsThree_TheOwnerTwo
#// Owner ≠ controller: P2 controls P1's Holdo. The extra card follows CONTROL — P2 draws 3, P1 draws 2.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP2GroundArenaControlled: IC27_038:1
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:IC27_038
P1HANDCOUNT:2
P2HANDCOUNT:3

---

# PlayedThisRound_AcrossTheBoundary_StillDrawsThree
#// Played from hand this round (no "entered play" restriction), then a fresh request before the action
#// phase ends. Nothing is carried in memory: the draw step reads the board when it runs.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myhandCardIds:IC27_038}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_038
P1HANDCOUNT:3
P1DECKCOUNT:2
P2HANDCOUNT:2

---

# TwinSuns_HoldoOnSeat3_OnlySeat3DrawsThree
#// Three seats, no teams. The draw step visits every live seat and asks each one for its OWN extra cards:
#// Holdo on seat 3 makes seat 3 draw 3 and nobody else. (Parked on seat 3, not seat 1, so a draw count
#// read from the wrong seat cannot pass by accident.)

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP3GroundArena: IC27_038:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
WithP3Deck: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass

## EXPECT
SEATCOUNT:3
P1HANDCOUNT:2
P2HANDCOUNT:2
P3HANDCOUNT:3
P3DECKCOUNT:2
