# TWI_060 Trade Federation Shuttle (Unit, Space, Vigilance) — "When Played: If you control a damaged unit,
# create a Battle Droid token." A damaged SOR_046 is in play, so a Battle Droid is created.
## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TWI_060}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
