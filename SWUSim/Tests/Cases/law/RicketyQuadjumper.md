# RevealNonUnitExp
#// LAW_115 Rickety Quadjumper (1/3, space) — On Attack: you may reveal the top card of your deck. If
#// it's not a unit, give an Experience token to another unit (left on top). Top is SOR_251 (event) ->
#// Experience to SOR_095.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_115:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_251

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DECKCOUNT:1
