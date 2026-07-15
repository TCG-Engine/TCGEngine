# TS26_052 Sith Traditions (Upgrade +1/+1) — attached unit gains "On Attack: give an Experience token to
# this unit." SEC_080 (3/3 + upgrade = 4/4) attacks the enemy base; the On-Attack Experience makes it
# 5/5, so it deals 5 to the base.
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_052
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:5
