# TWI_016 Jango Fett (FRONT) — the trigger also fires on ABILITY damage from a friendly UNIT, not just
# combat. P1 plays LOF_259 Ravening Gundark (When Played: deal 1 to a ground unit) and targets the enemy
# 3/7 (survives, still ready). The damage source is a friendly unit, so Jango triggers: P1 exhausts Jango
# to exhaust that enemy unit. (Proves the SWU_DMG_SRC source-tracking path.)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TWI_016:1;myResources:5;handCardIds:LOF_259}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
