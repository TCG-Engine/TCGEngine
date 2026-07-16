# WhenDrawn_RevealDealDamage
#// LOF_148 — "When you draw this card during the action phase: if you control an Aggression leader or
#// base, you may reveal it. If you do, deal 2 damage to a unit and 2 damage to a base." P1 (Aggression
#// leader+base) plays SOR_111 (When Played: draw a card) with LOF_148 on top of the deck; drawing it
#// triggers the reveal → 2 to the enemy unit + 2 to the enemy base.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
