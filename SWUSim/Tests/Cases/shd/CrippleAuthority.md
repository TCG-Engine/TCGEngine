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
