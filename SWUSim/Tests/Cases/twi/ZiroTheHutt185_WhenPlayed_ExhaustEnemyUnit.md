# TWI_185 Ziro the Hutt (Unit 2/8, Ground, cost 5, Underworld/Hutt) — "When Played: For each opponent, you
# may exhaust a unit that player controls." Playing it lets P1 exhaust the enemy SOR_095.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5;handCardIds:TWI_185}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
