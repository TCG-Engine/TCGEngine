# LOF_121 The Purrgil King (4/12) — When Played: draw a card for each friendly unit with 7 or more
# remaining HP. P1 controls a 3/7 unit; the King (12 HP) also qualifies → draw 2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:LOF_121}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
