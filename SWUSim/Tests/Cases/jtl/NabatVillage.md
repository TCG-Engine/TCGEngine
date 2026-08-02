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
#// JTL_028 Nabat Village — "At the start of the first action phase, put 3 cards from your hand on the bottom
#// of your deck." P1 controls Nabat Village; the first action-phase-start that fires (here: advancing one
#// round — the harness can't invoke ActionPhaseStart at load) makes P1 move 3 hand cards to the bottom. P1
#// starts with 5 in hand / 5 in deck; the regroup draws 2 (→ 7 hand / 3 deck), then the bottom-3 runs → 4
#// in hand, 6 in deck. (Once per game — a later action phase does NOT re-fire it.)

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
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1HANDCOUNT:4
P1DECKCOUNT:6

---

# NoBottomThreeAtSecondActionPhase
#// JTL_028 Nabat Village — "When the FIRST action phase starts: put 3 cards from your hand on the bottom
#// of your deck." Once-per-game: the second action phase must NOT re-fire it. This drives two full rounds.
#// Round 1: hand 5 / deck 8 → regroup draws 2 (hand 7, deck 6) → the bottom-3 fires (answered) → hand 4,
#// deck 9. Round 2: regroup draws 2 → hand 6, deck 7, and NO bottom-3 prompt is raised (P1NODECISION) —
#// the hand is simply 2 bigger than it was, with nothing put back.
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
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:6
P1DECKCOUNT:7
P1NODECISION
