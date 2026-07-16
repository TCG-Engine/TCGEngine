# DebuffEnteredThisPhase
#// TWI_052 Hello There (Event, cost 3, Vigilance/Heroism) — "Choose a unit that entered play this phase.
#// It gets -4/-4 for this phase." SOR_046 is played this phase (marking it entered), then Hello There
#// gives it -4/-4 → power 0 (floored), HP 3.

## GIVEN
CommonSetup: bbw/grw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: TWI_052

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:3
