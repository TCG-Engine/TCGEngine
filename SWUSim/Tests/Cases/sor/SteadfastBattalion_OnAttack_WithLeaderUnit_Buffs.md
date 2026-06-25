# SOR_116 Steadfast Battalion — Unit 5/5, Ground, Overwhelm.
# "On Attack: If you control a leader unit, give a friendly unit +2/+2 for this phase."
# P1 controls a REAL deployed leader unit (Leia @1) → condition met. "A friendly unit" includes the
# leader unit, so the buff is a genuine 2-target choice; here it's put on Leia (myGroundArena-1).
# SOR_116 attacks the base for 5; Leia (4 power → 6 with +2/+2) then attacks for 6 → base takes 11.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2BASEDMG:11
