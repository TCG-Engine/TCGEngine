# TWI_059 Royal Guard Attaché (Unit 2/5, Ground, cost 2, Vigilance, Naboo/Trooper) — "When Played: Deal
# 2 damage to this unit." Non-optional, no target. Enters ground index 0 with 2 damage.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TWI_059}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_059
P1GROUNDARENAUNIT:0:DAMAGE:2
