# LOF_019 Vergence Temple — negative: P1 controls only a 3/3 unit (3 remaining HP < 4), so the
# regroup-start condition fails and no Force token is created.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:LOF_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1NOFORCE
