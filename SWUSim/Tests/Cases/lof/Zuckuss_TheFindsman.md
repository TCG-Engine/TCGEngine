# NameCardDeckDiscard
#// LOF_204 Zuckuss — On Attack: Name a card, then discard the top card of the defending player's deck. If a
#// card with that name is discarded, this unit gets +4/+0 for this attack. P1 names "Zeb Orrelios" (the top
#// of P2's deck is SOR_146 = Zeb Orrelios), so Zuckuss (4 power) attacks the base for 4+4 = 8.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LOF_204:1:0
WithP2Deck: SOR_146

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Zeb Orrelios

## EXPECT
P2BASEDMG:8
