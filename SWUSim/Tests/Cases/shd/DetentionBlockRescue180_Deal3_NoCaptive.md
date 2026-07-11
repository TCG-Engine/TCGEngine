# SHD_180 Detention Block Rescue (3-cost event, Aggression) — "Deal 3 damage to a unit. If that unit is
# guarding any captured cards, deal 6 damage instead." Against a unit guarding nothing (SOR_046), it deals 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_180
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
