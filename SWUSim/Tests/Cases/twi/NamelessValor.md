# TokenGainsOverwhelm
#// TWI_119 Nameless Valor (Upgrade +2/+2, cost 1, Command) — "Attach to a token unit. Attached unit gains
#// Overwhelm." Played on a Battle Droid token (TWI_T01, 1/1 → 3/3), it grants Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:TWI_119}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
