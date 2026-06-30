# JTL_007 Admiral Holdo (leader) — the buff also targets a unit with a RESISTANCE upgrade on it (not
# just a Resistance unit). Host JTL_069 Munificent Frigate (Separatist, 4/7) carries a Resistance pilot
# upgrade JTL_046 (+2/+2 → 6/9); Holdo's +2/+2 makes it 8/11. Proves the "Resistance upgrade" clause.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_046
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:11
P1LEADER:EXHAUSTED
