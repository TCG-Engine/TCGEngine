# DrawsOneFewer
#// SWUSim Replay Schema
JTL_021 Colossus — draw 1 fewer card in starting hand (P1 draws 5, resources 2 → hand 3); P2 normal base unaffected (hand 4)
## GIVEN
P1LeaderBase: SOR_014/JTL_021
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
P1HANDCOUNT:3
P2HANDCOUNT:4
P1RESCOUNT:2
P2RESCOUNT:2

---

# MulliganStillDrawsOneFewer
#// JTL_021 Colossus — the "draw 1 fewer" applies to the MULLIGAN redraw too, not just the initial draw.
#// P1 mulligans and still redraws only 5 (not 6); after resourcing 2, its hand is 3 (same as no-mulligan).

## GIVEN
P1LeaderBase: SOR_014/JTL_021
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganYes
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0

## EXPECT
P1HANDCOUNT:3
P2HANDCOUNT:4
P1RESCOUNT:2
