# ASH_226 Qi'ra (Ground, 9/7, cost 7) — When Played: you may discard a card from your hand; if you do,
# deal 3 damage to a unit. P1 plays Qi'ra, discards SOR_095, and deals 3 to SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:ASH_226,SOR_095}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
