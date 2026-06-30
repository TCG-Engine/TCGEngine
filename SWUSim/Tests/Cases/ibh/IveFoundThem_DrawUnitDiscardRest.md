# IBH_009 I've Found Them (Event, cost 2, Command) — Reveal the top 3 of your deck, draw a unit revealed
#   this way, then discard the other revealed cards. Top 3 = a Unit + 2 events; the Unit is drawn, the
#   two events are discarded (NOT bottomed).

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:3
P1NODECISION
