# DrawsThreeMore
#// SWUSim Replay Schema
JTL_028 Nabat Village — draw 3 more cards in starting hand (P1 draws 9, resources 2 → hand 7); P2 normal base unaffected (hand 4)
## GIVEN
P1LeaderBase: SOR_014/JTL_028
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0

## EXPECT
P1HANDCOUNT:7
P2HANDCOUNT:4
P1RESCOUNT:2
P2RESCOUNT:2

---

# BottomThreeAtFirstActionPhase
#// JTL_028 Nabat Village — "When the first action phase starts: put 3 cards from your hand on the bottom of
#// your deck." Official ruling (03/06/2025): resolved after setup, before the initiative player's first action.
#// P1 controls Nabat Village. The fixture loads straight into round 1's action phase, so the prompt is pending
#// at load (entering MAIN is the first-action-phase hook — 2026-09-14; this section used to play a whole round
#// first, because the bottom-3 lived only in ActionPhaseStart, which never runs for round 1). P1 starts with 5
#// in hand / 5 in deck → 2 in hand, 8 in deck. See NabatVillage_FirstActionPhaseTiming.md for the setup path.

## GIVEN
CommonSetup: grw/grw/{myBase:JTL_028;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:8
P1NODECISION

---

# NoBottomThreeAtSecondActionPhase
#// JTL_028 Nabat Village — "When the FIRST action phase starts: put 3 cards from your hand on the bottom
#// of your deck." Once-per-game: the second action phase must NOT re-fire it. Round 1: the prompt is pending
#// at load (see BottomThreeAtFirstActionPhase) — hand 5 / deck 8 → answered → hand 2, deck 11. A full round
#// later: regroup draws 2 → hand 4, deck 9, and round 2's action phase start raises NO bottom-3 prompt
#// (P1NODECISION) — the hand is simply 2 bigger, with nothing put back. (Rewritten 2026-09-14 with the
#// round-1 timing fix; it used to answer the prompt at the start of round 2 and check round 3.)
#// Companion negative to BottomThreeAtFirstActionPhase above, which proves the first phase DOES fire.
#// ⚠ Both decks are seeded deep enough that neither empties across two regroups (an empty deck would add
#// the +6 base penalty and draw fewer cards, corrupting the hand/deck counts this section asserts).

## GIVEN
CommonSetup: grw/grw/{myBase:JTL_028;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:4
P1DECKCOUNT:9
P1NODECISION
