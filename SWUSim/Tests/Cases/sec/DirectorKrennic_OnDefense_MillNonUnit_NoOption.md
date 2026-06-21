# SEC_090 Director Krennic — when the milled card is NOT a unit (SOR_251 Confiscate, an Event), there is
#   no return option: the card just stays milled in the discard and combat proceeds with no decision.
#   Proves the "if it's a unit" gate and that a non-unit mill doesn't hang combat.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0
WithP2Deck: SOR_251
WithP2Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2DECKCOUNT:2
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION
