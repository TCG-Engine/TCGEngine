# Bounty_DrawCard
#// SHD_134 Guavian Antagonizer (1-cost 2/3, Saboteur + "Bounty — Draw a card"). Battlefield Marine
#// defeats it exactly (3 = HP 3); P1 collects and draws. Saboteur is registry-covered.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_134:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
