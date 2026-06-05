# SOR_246 You're My Only Hope (Event, cost 3, Heroism) — Look at the top card; you may play it for
# 5 resources less (free if your base has ≤5 remaining HP). Base is healthy here → the −5 discount
# applies. Vigilance/Heroism deck (byw): top card SOR_049 Obi-Wan Kenobi (cost 6, Vigilance/Heroism,
# Sentinel — no entry trigger). P1 has 4 resources: pays 3 for the event → 1 left, then plays
# Obi-Wan for 6 − 5 = 1 → 0 left. The −5 is what makes it playable (a normal cost-6 unit is
# unaffordable on 1 resource), so Obi-Wan in the arena proves the reduction. Deck 3→2.

## GIVEN
CommonSetup: byw/byw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_049
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_049
P1DECKCOUNT:2
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
