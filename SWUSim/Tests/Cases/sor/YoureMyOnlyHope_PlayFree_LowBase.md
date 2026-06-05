# SOR_246 You're My Only Hope — free-play branch: when your base has 5 or less remaining HP you may
# play the top card for FREE instead of −5. P1's base (SOR_024, 30 HP) has 25 damage → 5 remaining
# → the free branch is taken. Vigilance/Heroism deck (byw): top card SOR_056 Bendu (cost 6,
# Sentinel — no entry trigger). P1 pays 3 for the event → 0 resources, then plays Bendu for free.
# A cost-6 unit on 0 resources can ONLY come down via the free branch (the −5 discount would still
# leave a cost > 0), so Bendu in the arena proves the free play. Deck 3→2.

## GIVEN
CommonSetup: byw/byw/{myResources:3;myBaseDamage:25}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_056
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_056
P1DECKCOUNT:2
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
