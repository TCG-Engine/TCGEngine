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

---

# NameMismatch_NoBonus
#// LOF_204 Zuckuss — if the discarded top card does NOT match the named card, Zuckuss gets no +4/+0. P1 names
#// "Zeb Orrelios" but the top of P2's deck is LAW_124 (Industrious Team), so it is discarded with no match;
#// Zuckuss (4 power) deals just 4 to the base.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LOF_204:1:0
WithP2Deck: LAW_124

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Zeb Orrelios

## EXPECT
P2BASEDMG:4
P2DISCARDCOUNT:1

---

# EmptyDeck_NoDiscardNoBonus
#// LOF_204 Zuckuss — with an empty defending deck there is nothing to discard, so Zuckuss cannot match his
#// named card and gets no +4/+0; he deals just his 4 combat to the base. (Implementation note: the naming
#// prompt could be skipped entirely on an empty deck, but SWUSim still asks you to name a card
#// first — naming is a distinct instruction — then finds nothing to discard. Same net outcome.)

## GIVEN
CommonSetup: yyk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_204:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Zeb Orrelios

## EXPECT
P2BASEDMG:4
P2DECKCOUNT:0
