# ASH_136 Display of Strength (Event, cost 2) — Give a unit +3/+3 for this phase. P1 buffs SOR_095 (3/3
# → 6/6).
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_136}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
