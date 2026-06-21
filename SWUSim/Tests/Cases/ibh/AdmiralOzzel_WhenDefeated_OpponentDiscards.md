# IBH_082 Admiral Ozzel (Ground, 3/1, Aggression/Villainy) — When Defeated: each opponent discards a
#   card from their hand. Ozzel attacks a 4/7 and dies to the 4 counter; the opponent (P2, holding
#   exactly 1 card) discards it. Driven as P1's own attack so the trigger resolves inline.

## GIVEN
CommonSetup: rrk/bbk/{theirHandCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: IBH_082:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2HANDCOUNT:0
