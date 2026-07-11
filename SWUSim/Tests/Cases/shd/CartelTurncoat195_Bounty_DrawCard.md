# SHD_195 Cartel Turncoat (1-cost 2/3 space) — "Bounty — Draw a card." Munificent Frigate defeats
# it (4 ≥ 3); P1 collects and draws the seeded card.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_195:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
