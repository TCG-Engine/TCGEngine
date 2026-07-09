# SHD_154 Wrecker (6-cost 7/6 ground) — Overwhelm + "When Played: You may defeat a friendly resource. If
# you do, deal 5 damage to a ground unit." P1 plays Wrecker (7 resources), defeats one resource (→ 6 total),
# then deals 5 to the enemy SOR_046 (7 HP → 5 damage).

## GIVEN
CommonSetup: rrw/rrw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_154
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESCOUNT:6
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
