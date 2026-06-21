# LOF_139 Battle Fury — attached gains "On Attack: discard a card from your hand." SOR_095 (with Battle
# Fury) attacks the base and P1 discards its only hand card.

## GIVEN
CommonSetup: rrk/ggw/{handCardIds:SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_139

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
