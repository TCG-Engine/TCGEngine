# SOR_062 Regional Governor (Unit 1/4, cost 2, Vigilance) — "When Played: Name a card. While this
# unit is in play, opponents can't play the named card." P1 plays Governor and names "Battlefield
# Marine". On P2's turn, P2 tries to play their Battlefield Marine (SOR_095) — it is BLOCKED: the
# card stays in hand, no resources spent.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2RESAVAILABLE:2
