# DealOneAdvantageOnKill
#// ASH_146 Justifier (Space, 4/5) — When Played/On Attack: you may deal 1 to a unit; if it's defeated this
#// way, give an Advantage token to a unit. Deals 1 to a 3/1 Stormtrooper (dies) → Advantage to itself.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:ASH_146}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
