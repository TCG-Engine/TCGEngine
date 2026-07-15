# TS26_031 Chaotic Diversion (Event, cost 1) — Ready an enemy unit (it can't attack you this phase), then
# give a Shield to a friendly unit. The exhausted enemy SEC_080 is readied; the friendly SOR_095 is
# shielded. (The can't-attack-you restriction uses the shared CANT_ATTACK phase marker.)
## GIVEN
CommonSetup: ryk/rrk/{myResources:1;handCardIds:TS26_031}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:0:0 LAW_124:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
