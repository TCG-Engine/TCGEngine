# WhenPlayed_ReturnsDefeated
#// TWI_086 Admiral Trench (Unit 5/5, Ground, cost 7) — "Exploit 1. When Played: Return up to 3 units
#// that were defeated this phase from your discard pile to your hand." First SOR_128 (3/1) attacks
#// SOR_046 and dies (defeated this phase → its CardID enters the defeated-this-phase multiset). Then
#// Trench is played (no friendly units left → Exploit auto-skips) and its When Played offers SOR_128 in
#// discard; returning it puts SOR_128 back in hand.

## GIVEN
CommonSetup: gyk/grw/{myResources:7;handCardIds:TWI_086}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_086
P1HANDCOUNT:1
P1DISCARDCOUNT:0
