# Draw_OppFewerResources_NoDiscard
#// SHD_156 — the discard is conditional on the opponent controlling MORE resources than you. Here P1
#// has 5 resources and P2 only 3 → P1 still draws, but P2 keeps its hand (no discard).

## GIVEN
CommonSetup: rrw/rrw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_156
WithP1Deck: SOR_095
WithP2Resources: 3
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0

---

# Draw_OppMoreResources_Discards
#// SHD_156 (2-cost Aggression/Heroism event) — "Draw a card. Each opponent who controls more resources
#// than you discards a card from their hand." P1 has 2 resources, P2 has 5 (more) → P1 draws, and P2
#// (its lone hand card) discards.

## GIVEN
CommonSetup: rrw/rrw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_156
WithP1Deck: SOR_095
WithP2Resources: 5
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:0
P2DISCARDCOUNT:1

---

# TwinSuns_OnlyTheOpponentsWithMoreResourcesDiscard
#// ⚠ THE SEAT-COUNT CELL, and the sharpest shape in the "each opponent" family: the clause carries a
#// PER-SEAT TEST — "each opponent WHO CONTROLS MORE RESOURCES THAN YOU". The old code read
#// OtherPlayer(), compared that one seat, and discarded once.
#// Here P1 has 4 resources; seat 2 has 6 (qualifies), seat 3 has 2 (does NOT), seat 4 has 7 (qualifies).
#// So exactly two of the three opponents discard — which is what separates "loop the seats" from "loop
#// the seats but test the wrong one", and a version where every opponent qualifies could not.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4; theirResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SHD_156
WithP2Hand: [SOR_095 SOR_046]
WithP3Hand: [SOR_095 SOR_046]
WithP4Hand: [SOR_095 SOR_046]
WithP3Resources: 2
WithP4Resources: 7
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P4>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P2HANDCOUNT:1
P3HANDCOUNT:2
P4HANDCOUNT:1
