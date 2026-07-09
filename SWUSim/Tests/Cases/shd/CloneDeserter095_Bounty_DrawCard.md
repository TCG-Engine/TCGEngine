# SHD_095 Clone Deserter (1-cost 2/3, Restore 1 + "Bounty — Draw a card"). Battlefield Marine
# (3/3) defeats it exactly; P1 collects and draws the seeded card. Restore 1 is registry-covered.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
