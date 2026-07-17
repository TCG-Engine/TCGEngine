# TwoUniqueAttackNoDamageTaken
#// TS26_59 Brothers (Event, cost 3, Command) — Attack with up to 2 unique units (one at a time); prevent
#// all combat damage that would be dealt to each of them. Dodonna (4/4) then Veers (3/3) attack LAW_124
#// (4/7): combined 7 damage kills it, and both attackers take 0 counter damage (prevented).
## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_59}
WithP1GroundArena: [SOR_242:1:0 SOR_230:1:0]
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:0
