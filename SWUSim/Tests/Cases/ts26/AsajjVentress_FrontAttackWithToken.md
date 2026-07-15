# TS26_007 Asajj Ventress (leader front) — Action [Exhaust]: attack with a token unit; it gets +1/+0 for
# this attack. The Battle Droid token (1 power) attacks the enemy base with +1 → deals 2.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:TS26_007}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2BASEDMG:2
P1LEADER:EXHAUSTED
