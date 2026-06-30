# JTL_013 Poe Dameron attached as pilot to SOR_225 (TIE/ln Fighter, Space) via leader action.
# Host becomes 4/2. P2 plays SOR_077 Takedown ("Defeat a unit with 5 or less remaining HP.")
# Host remaining HP = 2 ≤ 5 → valid target. Auto-defeated.
# After: host discarded, Poe returns to P1 leader zone (NOTDEPLOYED, NOT in discard).
# P1 space arena empty, P1 discard has SOR_225.

## GIVEN
CommonSetup: grw/brw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 1
WithP2Resources: 4
WithP2Hand: SOR_077
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_225
P2RESAVAILABLE:0
