# LAW_186 Enfys Nest's Helmet (Upgrade, +0/+2) — grants "On Attack: You may give another unit +3/+0 for
# this phase." SEC_080 (index 0) wears the Helmet and attacks the base; on attack P1 gives the other
# friendly SEC_080 (index 1) +3/+0 → its power becomes 6.

## GIVEN
CommonSetup: brk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_186
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:6
