# AttacksAndDefeats_MayDraw
#// SEC_088 First Light (Space, 5/7) — Ambush + "When this unit attacks and defeats a unit: you may draw
#//   a card." SEC_088 attacks and kills SOR_237 (2/3); P1 chooses to draw.

## GIVEN
CommonSetup: ggk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_088:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
