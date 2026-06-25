# LOF_003 Ahsoka Tano — Action [Exhaust, use the Force]: Give a friendly unit Sentinel for this phase. Plo
# Koon gains Sentinel and P1 loses the Force token.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:LOF_003;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NOFORCE
