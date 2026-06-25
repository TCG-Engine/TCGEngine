# SOR_105 General Krell (5/4) — "Each other friendly unit gains: 'When Defeated: You may draw a
# card.'" P1's 3/3 (granted by Krell) attacks into a 3/7 and dies; its granted When-Defeated lets
# P1 draw a card. Krell itself survives.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:1
