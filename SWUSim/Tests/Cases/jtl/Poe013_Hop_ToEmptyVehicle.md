# JTL_013 Poe Dameron (LEADER) — deployBox hop: move Poe from vehicle A to empty vehicle B.
# Setup: two SOR_225 (TIE/ln Fighter) in Space arena. Poe attaches to index-0 via leader action.
# Then: UseUnitAbility on index-0 host → hop → player picks index-1.
# After: index-0 has 0 upgrades, index-1 has JTL_013 (upgradePower=2, upgradeHp=1 → host 4/2).
# Once-per-round flag blocks a second hop the same round.
# Resources: 1 for leader action + 1 for hop = 2 total.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:HP:2
P1RESAVAILABLE:0
