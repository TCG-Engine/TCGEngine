# ASH_127 The Twins (Ground, 2/7, cost 4) — When Played: you may give another friendly unit Sentinel for
# this phase. P1 plays The Twins and gives SOR_095 Sentinel.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_127}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
