# TS26_033 Kouhun Assassination (Event, cost 3) — An opponent may discard a card from their hand. If they
# do, give a non-Vehicle unit -8/-8 for this phase. The opponent discards their card, then the caster
# debuffs a non-Vehicle enemy unit (SEC_080, 3/3) to death.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_033;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:0
P2GROUNDARENACOUNT:0
