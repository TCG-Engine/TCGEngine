# JTL_001 Asajj deployed as pilot on SOR_225 (TIE/ln Fighter, Space).
# P2 plays SOR_077 Takedown ("Defeat a unit with 5 or less remaining HP.") targeting the host.
# SOR_225 + JTL_001 → host 5/5; remaining HP = 5 ≤ 5 → eligible target.
# After: host goes to P1 discard, Asajj returns to P1 leader zone (NOTDEPLOYED, NOT in discard).
# P2_ENEMY_DEFEATED fired (SWU_ENEMY_DEFEATED counter); space arena empty.

## GIVEN
P1LeaderBase: JTL_001/SOR_022
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 6
WithP2Resources: 4
WithP2Hand: SOR_077
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_225
P2RESAVAILABLE:0
