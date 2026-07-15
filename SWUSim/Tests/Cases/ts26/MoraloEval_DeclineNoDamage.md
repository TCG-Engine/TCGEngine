# TS26_073 Moralo Eval (Unit 3/2) — "When your base is dealt combat damage: you may deal 1 damage to a
# unit." DECLINE branch: P1 declines, so SEC_080 takes no damage; the base still takes its 3.
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_073:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
