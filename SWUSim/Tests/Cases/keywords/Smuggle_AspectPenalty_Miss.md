## GIVEN
# SOR_014 (Sabine, Aggression+Heroism) + JTL_031 (Lake Country, no aspect) — no Command, no Villainy.
# SHD_086 Smuggle aspects [Command, Villainy]: both missing → +2+2 = +4 penalty.
# Effective Smuggle cost = 4 + 4 = 8. Needs 8 ready resources to succeed.
CommonSetup: nrw/grw
WithP1Resources: 1:SHD_086:0,8:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_086
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:9
P1RESAVAILABLE:0
