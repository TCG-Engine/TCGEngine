# SOR_013 Cassian Andor (leader) — Action [1 resource, Exhaust]: If you've dealt 3 or more damage to
# an enemy base this phase, draw a card. P1's Battlefield Marine (SOR_095, 3 power) attacks P2's base
# for 3, meeting the threshold; P1 then uses the leader action — pays 1 resource (1 → 0), Cassian
# exhausts, and the condition is met so P1 draws 1 (deck 1 → 0, hand 0 → 1).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
