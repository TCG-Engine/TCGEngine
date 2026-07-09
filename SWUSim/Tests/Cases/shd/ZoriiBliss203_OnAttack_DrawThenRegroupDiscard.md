# SHD_203 Zorii Bliss (4/7) — "On Attack: Draw a card. At the start of the regroup phase, discard
# a card from your hand." The attack draws 1; at regroup start the armed discard fires (MZCHOOSE
# over the hand) before the regroup draw. Net: 1 drawn − 1 discarded + 2 regroup draws = hand 2.

## GIVEN
CommonSetup: gyw/gyw
P1OnlyActions: true
WithP1GroundArena: SHD_203:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>AnswerDecision:myHand-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1DECKCOUNT:0
