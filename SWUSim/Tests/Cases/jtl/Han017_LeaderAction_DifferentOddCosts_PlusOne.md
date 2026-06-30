# JTL_017 Han Solo (leader) — Action [Exhaust]: Reveal the top card of your deck, then attack with a
# unit. If the revealed card and that unit have DIFFERENT odd costs, that unit gets +1/+0 for this
# attack. Revealed SOR_225 (cost 1, odd); attacker JTL_069 (cost 5, odd) — different odd costs → +1/+0,
# so it deals 4+1=5 to P2's base, then is back to power 4.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1Deck: SOR_225

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:5
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED
