# SEC_218 Cikatro Vizago (Ground, 3/4) — On Attack: reveal the top card of your deck. An opponent may
#   pay 1 resource. If they don't, draw that card. P2 declines → P1 draws the revealed card.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_218:1:0
WithP1Deck: SOR_095
WithP2Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
