# JTL_017 Han Solo (leader) — the +1/+0 requires BOTH costs to be odd. The attacker SOR_095 has an even
# cost (2), so even though the revealed SOR_225 is odd (1) the condition fails and no buff is granted:
# SOR_095 deals its base 3 to P2's base.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_225

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
