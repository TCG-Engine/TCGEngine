# IBH_085 Admiral Ozzel (reprint of IBH_082) — When Defeated: each opponent discards a card. Confirms duplicate.

## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: IBH_085:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
