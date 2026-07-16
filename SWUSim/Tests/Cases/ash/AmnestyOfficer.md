# ExhaustKeywordUnit
#// ASH_214 Amnesty Officer (Ground, 2/2, cost 2) — When Played: you may exhaust a unit with one or more
#// keywords. The enemy SOR_063 (Sentinel) qualifies; P1 exhausts it.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_214}
WithP2GroundArena: SOR_063:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:EXHAUSTED
