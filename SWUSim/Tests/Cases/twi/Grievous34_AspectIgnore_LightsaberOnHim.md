# TWI_034 General Grievous (Unit 4/4, cost 3, Separatist/Official) — "Ignore the aspect penalty on each
# Lightsaber upgrade you play on this unit." Under an Aggression base+leader, TWI_121 General's Blade
# (Command Lightsaber, cost 3) is off-aspect (+2 penalty → 5). Played onto Grievous (the only friendly
# host) the penalty is waived → it costs its printed 3 and attaches with exactly 3 resources.
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:TWI_121}
P1OnlyActions: true
WithP1GroundArena: TWI_034:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_034
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:0
