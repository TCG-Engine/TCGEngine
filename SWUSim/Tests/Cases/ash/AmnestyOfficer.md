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

---

# NoKeywordUnit_NoExhaust
#// ASH_214 Amnesty Officer — the exhaust needs a unit with one or more keywords. With only the vanilla
#// SOR_095 (no keywords) in play, no unit is offered and nothing is exhausted.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_214}
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:READY
