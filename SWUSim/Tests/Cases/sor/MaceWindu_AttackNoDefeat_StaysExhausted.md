# SOR_149 Mace Windu — the ready only triggers on a DEFEAT. Mace attacks a 3/7 that survives his
# 5 damage, so he is NOT readied and stays exhausted.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:EXHAUSTED
