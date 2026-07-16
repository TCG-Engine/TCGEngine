# DebuffAndDraw
#// SEC_075 Knowledge and Defense (event, cost 3) — Give a unit -2/-2 for this phase. Draw a card. P1
#//   debuffs the enemy SOR_046 (3/7 → 1/5) and draws 1 (SEC_075 played, 1 drawn → hand 1).

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_075
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
P1HANDCOUNT:1
