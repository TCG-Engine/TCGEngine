# SHD_217 Tobias Beckett — "When you play a non-unit card: You may exhaust a unit that costs the same as
# or less than the card you played. Once each round." P1 plays SHD_178 (event, cost 1); its own deal-2
# hits the enemy SHD_095, then Tobias exhausts SHD_095 (cost 1 ≤ 1).

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SHD_217:1:0
WithP1Hand: SHD_178
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
