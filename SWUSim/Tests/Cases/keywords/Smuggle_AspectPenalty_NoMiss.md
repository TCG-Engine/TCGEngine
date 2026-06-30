## GIVEN
# SOR_007 (Tarkin, Command+Villainy) + SOR_024 (Echo Base, Command)
# Provides Command + Villainy — SHD_086 Smuggle aspects [Command, Villainy] fully covered, no penalty.
# Effective Smuggle cost = 4. With 4 ready resources, the play succeeds.
CommonSetup: ggk/grw
WithP1Resources: 1:SHD_086:0,4:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_086
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:5
P1RESAVAILABLE:0
