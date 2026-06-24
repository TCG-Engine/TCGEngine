# ASH_133 Trask Walker (Ground, 5/9, cost 8) — When Played: choose a unit in your discard pile costing 7
# or less; either put it on the bottom of your deck and heal 3 from your base, OR return it to your hand.
# Here the discarded SOR_095 (cost 2) is the only choice (auto-resolved); choosing "Bottom" heals P1's
# base from 5 damage to 2 and clears the discard pile.
## GIVEN
CommonSetup: ggk/ggk/{myResources:8;handCardIds:ASH_133;discardCardIds:SOR_095;myBaseDamage:5}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Bottom
## EXPECT
P1BASEDMG:2
P1DISCARDCOUNT:0
