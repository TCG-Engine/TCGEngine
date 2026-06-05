## GIVEN
# SOR_009 (Leia Organa, Command+Heroism) + SOR_024 (Echo Base, Command) — provides 2×Command+Heroism.
# SHD_248 (Tech): Smuggle cost 4, Smuggle aspects [Heroism] — covered → own path = 4.
# Tech path: CardCost(3) + 2 + CardAspectPenalty([Heroism]=0) = 5. Own Smuggle wins (4 < 5).
# 4 ready resources is enough via own path but not via Tech path (5). Proves own path used.
CommonSetup: ggw/grw
WithP1GroundArena: SHD_248
WithP1Resources: 1:SHD_248:0,4:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_248
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESCOUNT:5
P1RESAVAILABLE:0
