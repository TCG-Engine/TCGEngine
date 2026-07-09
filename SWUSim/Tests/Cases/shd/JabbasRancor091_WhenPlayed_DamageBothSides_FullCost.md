# SHD_091 Jabba's Rancor (8-cost 9/9 ground, Command/Villainy) — When Played: Deal 3 to another friendly
# ground unit AND 3 to an enemy ground unit. Without Jabba the cost is the full 8 (grk leader/base cover
# Command+Villainy, no penalty → 8 spent, 0 left). Friendly damage lands on SOR_046 (7 HP); enemy on SEC_080.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SHD_091
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
