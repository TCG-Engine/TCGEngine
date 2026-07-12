# TWI_160 Vanguard Droid Bomber (Unit 2/2, Space, cost 2, Aggression, Separatist/Droid/Vehicle/Fighter) —
# "When Played: If you control another Separatist unit, deal 2 damage to an enemy base." With a friendly
# Battle Droid token (TWI_T01, Separatist) already in play, playing the bomber deals 2 to the enemy base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_160}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_160
P2BASEDMG:2
