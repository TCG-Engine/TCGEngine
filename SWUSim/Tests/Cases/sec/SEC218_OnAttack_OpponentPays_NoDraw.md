# SEC_218 — if the opponent pays 1 resource, the attacker does NOT draw the revealed card (it stays on
#   top of the deck). P2 pays → P1 hand stays empty, deck still holds the card.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_218:1:0
WithP1Deck: SOR_095
WithP2Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:0
P1DECKCOUNT:1
