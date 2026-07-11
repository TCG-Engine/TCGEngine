# SOR_246 You're My Only Hope — "Look at the top card. You may play it (5 less; free if your base has
# ≤5 remaining HP)." With a healthy base the discount is only −5, so "Play" must be gated on affordability:
# if the player can't pay cost−5, only "Leave" applies (no prompt / no Play option).
#
# SOR_246 costs 3 (Heroism, covered) → after playing it P1 has 0 ready resources. Base is full (30 HP > 5)
# so the free branch does NOT apply. Top card SOR_049 Obi-Wan (cost 6) → 6 − 5 = 1 net > 0 → UNaffordable.
# (Companions: YoureMyOnlyHope_PlayDiscount covers the affordable −5 case, YoureMyOnlyHope_PlayFree_LowBase
# the free branch — both must still offer Play.)

## GIVEN
CommonSetup: byw/byw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_049
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
