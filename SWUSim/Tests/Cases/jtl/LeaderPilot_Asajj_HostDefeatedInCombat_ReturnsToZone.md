# JTL_001 Asajj pre-placed as a pilot on a measly JTL_T01 TIE token (1/1 → 4/5 with Asajj's +3/+4).
# P2 attacks with SOR_086 (5/6): host takes 5 (=5 HP, defeated); attacker takes 4 (survives at 6 HP).
# Combat-defeat of a leader-piloted vehicle → host leaves play, Asajj returns to the leader zone
# (NOTDEPLOYED, NOT in discard). P2's attacker survives.

## GIVEN
P1LeaderBase: JTL_001:1:1/SOR_022
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArenaUpgrade: 0:JTL_001
WithP2SpaceArena: SOR_086:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1LEADER:NOTDEPLOYED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_086
