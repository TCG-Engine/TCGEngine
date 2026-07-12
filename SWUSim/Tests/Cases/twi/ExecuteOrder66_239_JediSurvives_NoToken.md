# TWI_239 Execute Order 66 — a Jedi with more than 6 HP (JTL_251, 7 HP) takes the 6 damage but survives,
# so NO Clone Trooper is created. Proves the token comes only from a DEFEAT, not from being hit.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4;handCardIds:TWI_239}
P1OnlyActions: true
WithP1SpaceArena: JTL_251:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_251
P1SPACEARENAUNIT:0:DAMAGE:6
P1GROUNDARENACOUNT:0
