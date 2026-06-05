# JTL_018 Kazuda Xiono (leader) — deployed as a PILOT, the host Vehicle gains his "On Attack: Choose any
# number of friendly units. They lose all abilities for this round." Kazuda deploys as a Pilot onto the
# lone friendly Vehicle (SOR_237, now 2+3=5 power), the host attacks the base, and its granted On Attack
# strips SOR_063 Cloud City Wing Guard of Sentinel for the round.
#
# This works through the GENERIC OnAttackFromUpgrade seam (it reuses $onAttackAbilities["JTL_018:0"] for
# any upgrade whose CardID has that key) — no JTL_018-specific wiring needed. Guard test only.

## GIVEN
P1LeaderBase: JTL_018/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
