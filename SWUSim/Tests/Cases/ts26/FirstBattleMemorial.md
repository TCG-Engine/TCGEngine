# EpicExpPerLeaderUnit
#// TS26_009 First Battle Memorial (Base, Vigilance) — Epic Action: for each friendly leader unit, give an
#// Experience token to a unit. With one deployed leader unit, give 1 Experience to SEC_080 (3 → 4 power).
## GIVEN
CommonSetup: bbw/rrk/{myBase:TS26_009;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1BASE:EPICUSED
