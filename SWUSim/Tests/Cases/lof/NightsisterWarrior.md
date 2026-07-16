# WhenDefeated_Draw
#// LOF_059 Nightsister Warrior (2/2) — When Defeated: draw a card. She attacks a 4/7, dies to the counter,
#// and P1 draws.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_059:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
