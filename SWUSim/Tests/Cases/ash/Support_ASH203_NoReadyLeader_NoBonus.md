# ASH_203 Mando's N-1 Starfighter — the +2/+0 is gated on a ready leader to exhaust. With the leader
# already exhausted, no option is offered and the Starfighter deals only its base 1 to the enemy base.
## GIVEN
CommonSetup: yyk/yyk/{myLeaderReady:0}
WithP1SpaceArena: ASH_203:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:1
P1LEADER:EXHAUSTED
