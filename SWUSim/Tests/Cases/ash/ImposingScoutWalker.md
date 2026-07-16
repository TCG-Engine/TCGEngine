# DealThreeAdvantageOnKill
#// ASH_176 Imposing Scout Walker (Ground, 4/6) — When Played: you may deal 3 to a ground unit; if it's
#// defeated this way, give 3 Advantage tokens to this unit. Kills a 3/1 Stormtrooper → 3 Advantage to self.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_176}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_176
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
