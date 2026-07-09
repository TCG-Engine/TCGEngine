# SHD_178 Daring Raid (1-cost event, Aggression) — "Deal 2 damage to a unit or base." P1 targets the enemy
# SOR_046 (7 HP → 2 damage).

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
