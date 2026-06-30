# JTL_015 Rio Durant (leader) — the attacking space unit gains Saboteur for this attack (defeat the
# defender's Shields). The X-Wing (SOR_237, power 2 → 3 with the +1) attacks a Shielded TIE (SOR_225,
# 2/1 + a Shield token): Saboteur defeats the Shield, so the 3 damage goes through and the TIE is
# defeated. The TIE's counter (power 2) damages the surviving X-Wing.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
