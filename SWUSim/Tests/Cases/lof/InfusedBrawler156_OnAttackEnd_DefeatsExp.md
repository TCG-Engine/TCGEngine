# LOF_156 Infused Brawler — "When this unit completes an attack: defeat an Experience token on it." With
# one Experience token (power 2+1=3), it attacks the base for 3, then loses the Experience token.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_156:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
