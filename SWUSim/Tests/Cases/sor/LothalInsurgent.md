# AnotherCard_OppDrawsDiscards
#// SOR_190 Lothal Insurgent (Unit 3/2, cost 2, Cunning/Heroism) — "When Played: If you played another
#// card this phase, each opponent draws a card then discards a random card from their hand." P1 first
#// plays a throwaway (SOR_210), then plays Lothal → the "another card this phase" condition is met.
#// P2's hand is empty and their deck top is SOR_171, so P2 draws SOR_171 then discards it (the only
#// card → the random discard is deterministic): P2 hand stays 0, P2 discard +1 (From HAND), deck -1.
#// COVERAGE: offer=N/A (no target choice — the draw+random-discard resolves without any picker)
#//           decline=N/A (mandatory clause, no "you may") · control=N/A (keys on cards YOU played, no
#//           unit-identity or controller lookup) · boundary pair=AnotherCard_OppDrawsDiscards (condition
#//           met) vs FirstCard_NoEffect (condition unmet) + PreviousPhaseOnly_NoEffect (phase boundary
#//           resets the count) · reqboundary=WaylaidAndReplayed_CountsItsOwnEarlierPlay (the
#//           played-this-phase memory survives multiple action round-trips, incl. an opposing play)

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_190
WithP2Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2HANDCOUNT:0
P2DECKCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND

---

# FirstCard_NoEffect
#// SOR_190 Lothal Insurgent — guard: if Lothal is the FIRST card played this phase, the "if you
#// played another card this phase" condition fails → no opponent draw/discard. P2's hand and deck are
#// untouched.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_190
WithP2Deck: SOR_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:1
P2DISCARDCOUNT:0

---

# RandomDiscard_FromMultipleCards
#// SOR_190 Lothal Insurgent — with a NON-empty opponent hand the discard is random across the whole
#// hand (including the just-drawn card). P1 plays a throwaway then Lothal; P2 holds 2 cards and has 1
#// in deck. P2 draws (hand 3), then randomly discards 1 → hand 2, discard 1, deck 0. Which card lands
#// in the discard is random, so only the zone COUNTS are pinned — total hand+discard is conserved.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_210
WithP1Hand: SOR_190
WithP2Hand: [SOR_164 SOR_232]
WithP2Deck: SOR_178

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2DECKCOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:FROM:HAND

---

# PreviousPhaseOnly_NoEffect
#// SOR_190 Lothal Insurgent — the "another card this phase" count resets at the phase boundary. P1
#// plays a throwaway, the round crosses regroup (both players decline the optional resource; decks are
#// seeded so the regroup draws don't hit an empty deck), and in the NEXT action phase P1 plays Lothal
#// as their first card → no trigger. P2 drew 2 at regroup (hand 2, deck 1) and is untouched by Lothal.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
WithP1Hand: [SOR_210 SOR_190]
WithP1Deck: [SOR_128 SOR_128]
WithP2Deck: [SOR_164 SOR_232 SOR_078]

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Claim
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_190
P2DECKCOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# WaylaidAndReplayed_CountsItsOwnEarlierPlay
#// SOR_190 Lothal Insurgent — "another card this phase" counts a PREVIOUS play of this same physical
#// card. P1 plays Lothal (first card → no trigger), P2 returns it to hand with Waylay (SOR_222), and
#// P1 replays it: the earlier Lothal play satisfies the condition → P2 (hand now empty after Waylay)
#// draws their last card and must discard it → deck 0, hand 0, discard 2 (Waylay + the drawn card).

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:3;theirhandCardIds:SOR_222}
WithP1Hand: SOR_190
WithP2Deck: SOR_232

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_190
P2DECKCOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:2
