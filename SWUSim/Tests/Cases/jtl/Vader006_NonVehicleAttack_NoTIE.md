# JTL_006 Darth Vader (leader) — the TIE is only created if you attacked with a VEHICLE this phase.
# Here P1 attacks with a non-Vehicle ground unit (SEC_080), so the condition is not met and no token
# is created (the leader still exhausts). Proves the "non-token Vehicle" requirement.

## GIVEN
P1LeaderBase: JTL_006/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0
P2BASEDMG:3
P1LEADER:EXHAUSTED
P1NODECISION
