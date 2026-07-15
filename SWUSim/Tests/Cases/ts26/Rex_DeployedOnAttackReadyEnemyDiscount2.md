# TS26_006 Rex (leader deployed, 5/6) — On Attack: you may ready an exhausted enemy unit; if you do, the
# next event you play this phase costs 2 less. Rex attacks LAW_124, readies the exhausted SEC_080, then
# Urgent Mission (cost 2) plays for 0 (0 resources — only via the -2 discount), dealing 2 to P1's base.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_006:1:1;myResources:0;handCardIds:TS26_064}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [LAW_124:1:0 SEC_080:0:0]
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:1:READY
P1BASEDMG:2
