# JTL_001 Asajj pre-placed as a pilot on a measly JTL_T01 TIE token (1/1 → 4/5 with Asajj's +3/+4).
# P2 attacks with SOR_086 (5/6): host takes 5 (=5 HP, defeated); attacker takes 4 (survives at 6 HP).
# Combat-defeat of a leader-piloted vehicle → host leaves play, Asajj returns to the leader zone
# (NOTDEPLOYED, NOT in discard). P2's attacker survives.

## GIVEN
CommonSetup: gbk/brw/{
  myLeader:JTL_001;
  myBase:SOR_022;
  myLeaderDeployedPilot:1;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1SpaceArena: JTL_T01:1:0
WithP2SpaceArena: SOR_086:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1LEADER:NOTDEPLOYED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_086
