# DealsFirstVsExhausted
#// JTL_185 Hound's Tooth — While attacking an exhausted unit that didn't enter play this phase, it deals
#// combat damage before the defender. Hound's Tooth (3 power) attacks the exhausted SOR_225 (2/1):
#// SOR_225 is defeated before it can deal its counter, so Hound's Tooth takes 0 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_185:1:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_185
P1SPACEARENAUNIT:0:DAMAGE:0
