# TS26_078 Barriss Offee (Unit 5/6) — "When an enemy unit attacks: you may give an Experience token to
# that unit." DECLINE branch: P1 declines, so SEC_080 stays 3 power and deals 3 to the base.
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_078:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P1BASEDMG:3
