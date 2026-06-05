# JTL_013 Poe Dameron (LEADER) — Leader Action [1 resource, Exhaust]: flip + attach as Pilot.
# SOR_225 (TIE/ln Fighter, power=2, hp=1) is the host Vehicle (0 pilots). JTL_013 upgradePower=2, upgradeHp=1.
# After: host power=4, hp=2. Leader: Deployed, Exhausted, EpicAction NOT used.
# Host is NOT a Leader Unit (JTL_013 not in leaderCanDeployAsUpgrade — explicit NOTLEADERUNIT check).
# Resources: 1 spent on action cost. Base SOR_022 (Vigilance) + Leader JTL_013 (Aggression+Heroism).

## GIVEN
P1LeaderBase: JTL_013/SOR_022
P2LeaderBase: JTL_013/SOR_022
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 1
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_013
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:2
P1SPACEARENAUNIT:0:NOTLEADERUNIT
P1LEADER:DEPLOYED
P1LEADER:EXHAUSTED
P1LEADER:EPICAVAILABLE
P1RESAVAILABLE:0
