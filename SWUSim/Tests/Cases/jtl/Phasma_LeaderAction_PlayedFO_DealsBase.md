# JTL_010 Captain Phasma (leader) — Action [Exhaust]: If you played a First Order card this phase, deal
# 1 damage to a base. P1 plays JTL_081 (First Order, cost 1 — Command base + Phasma's Villainy cover it),
# then Phasma's action deals 1 to the enemy base.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_081
WithP1Resources: 1

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1LEADER:EXHAUSTED
