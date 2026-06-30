# LOF_194 J-Type Nubian Starship — When Defeated: discard a card from your hand. It attacks a 4/7, dies,
# and P1 discards its only hand card.

## GIVEN
CommonSetup: yyw/rrk/{handCardIds:SOR_095}
P1OnlyActions: true
WithP1SpaceArena: LOF_194:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P1HANDCOUNT:0
P1SPACEARENACOUNT:0
