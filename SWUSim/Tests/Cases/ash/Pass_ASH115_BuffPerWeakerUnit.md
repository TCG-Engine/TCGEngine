# ASH_115 The Student Guides the Master (Event, cost 1) — Give a friendly unit +1/+0 for this phase for
# each other friendly unit with less power than it. P1 buffs SOR_095 (3 power); two other friendly units
# (SOR_237 and SOR_225, each 2 power) have less power, so SOR_095 gets +2 → 5.
## GIVEN
CommonSetup: ggw/ggk/{myResources:1;handCardIds:ASH_115}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
