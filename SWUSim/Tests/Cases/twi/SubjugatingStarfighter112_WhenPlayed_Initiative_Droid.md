# TWI_112 Subjugating Starfighter (Unit 3/3, Space, cost 4, Command, Separatist/Vehicle/Fighter) — Ambush +
# "When Played: If you have the initiative, create a Battle Droid token." With P1 holding claimed
# initiative, playing it creates a Battle Droid. Base g + leader gk cover the Command pip.

## GIVEN
CommonSetup: ggk/bbw/{myResources:4;handCardIds:TWI_112}
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_112
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
