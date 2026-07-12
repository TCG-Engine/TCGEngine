# TWI_229 Battle Droid Escort (Unit 1/1, Ground, cost 3, Separatist/Droid/Trooper) — "When Played/When
# Defeated: Create a Battle Droid token." Playing it creates a Battle Droid.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_229}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_229
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
