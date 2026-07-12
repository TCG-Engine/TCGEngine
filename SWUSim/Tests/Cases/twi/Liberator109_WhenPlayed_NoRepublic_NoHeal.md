# TWI_109 501st Liberator — condition guard: with no OTHER Republic unit (the Liberator itself doesn't
# count), no heal is offered and P1's base stays at 5 damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:5;handCardIds:TWI_109}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:TWI_109
