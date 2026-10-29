# DiscardedFromHand_ByOpponent_PlayableFromDiscardAtCost
#// IC27_063 Kylo's TIE Silencer — Follow My Lead (4/4 Space, Command/Villainy, First Order·Vehicle·Fighter, unique)
#// "Restore 1. When this unit is discarded from your hand: For this phase, you may play it from your discard pile
#// (paying its cost)."
#// Restore 1 is printed (auto-wired). The rest is SHD_135's mechanism restricted to the HAND: a synchronous
#// $cardDiscardedHandlers entry stamps the discard entry with TPP (play from your discard at cost this phase,
#// cleared at regroup) when it came From HAND — never from the deck or from play. It never downgrades a better
#// permission already on the entry.
#//
#// COVERAGE: offer=N/A (the replay is a board action on a discard card, not a target pool) ·
#//           decline=N/A ("you may play" = an optional board action; not taking it is the decline, as every
#//           negative here does) ·
#//           boundary=Unaffordable_NoOp (one resource short) paired with the positive (full cost) ·
#//           control=OpponentCannotPlayItFromYourDiscard (the permission is the discard OWNER's) ·
#//           reqboundary=DiscardedFromHand_BySelf_PlayableFromDiscard (a SimulateRequestBoundary between the
#//           discard and the replay) + StampExpiresNextPhase ·
#//           modes=2P only ("your hand"/"your discard pile" — self-only)
#//
#// P1's SHD_181 Pillage makes P2 discard 2 cards (the Silencer and a Marine). P2 replays the Silencer from its
#// discard, paying 4.

## GIVEN
CommonSetup: rrk/ggk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:IC27_063,SOR_095;theirResources:4}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:IC27_063
P2RESAVAILABLE:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095

---

# Unaffordable_NoOp
#// "(paying its cost)": 3 ready resources against a cost of 4 — the play is a silent no-op.

## GIVEN
CommonSetup: rrk/ggk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:IC27_063,SOR_095;theirResources:3}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P2RESAVAILABLE:3

---

# DiscardedFromHand_BySelf_PlayableFromDiscard
#// The self-chosen discard path (DoDiscardCard): P1's IC27_145 Poggle pays 1 and discards the Silencer from P1's
#// own hand; after a request boundary P1 replays it. Poggle's own payoff ("the next card you play this phase
#// costs 3 less") applies to that replay: 6 resources -> 5 (Poggle) -> 4 (Silencer at 4 - 3).

## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: IC27_063

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>PlayFromDiscard:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:IC27_063
P1DISCARDCOUNT:0
P1RESAVAILABLE:4

---

# DiscardedFromDeck_NotPlayable
#// "from your HAND" — unlike SHD_135, a deck mill does not count. P2's SOR_047 Kanan mills P1's top card (the
#// Silencer); P1 cannot play it from the discard.

## GIVEN
CommonSetup: ggk/grw/{theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_047:1:0
WithP1Resources: 4
WithP1Deck: IC27_063
WithP1Deck: SOR_095

## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P1>PlayFromDiscard:0

## EXPECT
P1DISCARDUNIT:0:CARDID:IC27_063
P1DISCARDUNIT:0:FROM:DECK
P1SPACEARENACOUNT:0
P1RESAVAILABLE:4

---

# DiscardedFromPlay_NotPlayable
#// Defeated (From PLAY) is not "discarded from your hand": the Silencer dies attacking a 4/7 frigate and cannot
#// be replayed.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: IC27_063:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayFromDiscard:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:IC27_063
P1RESAVAILABLE:4

---

# StampExpiresNextPhase

## GIVEN
CommonSetup: rrk/ggk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:IC27_063,SOR_095;theirResources:4}
WithP1Deck: [SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
PHASE:MAIN
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:IC27_063

---

# OpponentCannotPlayItFromYourDiscard

## GIVEN
CommonSetup: rrk/ggk/{handCardIds:SHD_181;myResources:8;theirHandCardIds:IC27_063,SOR_095;theirResources:4}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:IC27_063
