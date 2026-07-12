# TWI_198 Enfys Nest (Unit 5/7, Ground, cost 7, Cunning/Heroism, Underworld) — Saboteur + "When Played/
# On Attack: You may return an enemy non-leader unit with less power than this unit to its owner's hand."
# Enfys (power 5) can return SOR_095 (power 3 < 5) but NOT TWI_149 (power 6), so only SOR_095 is offered;
# it returns to P2's hand. Base y + leader yw cover both pips.

## GIVEN
CommonSetup: yyw/rrk/{myResources:7;handCardIds:TWI_198}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 TWI_149:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_149
P2HANDCOUNT:1
