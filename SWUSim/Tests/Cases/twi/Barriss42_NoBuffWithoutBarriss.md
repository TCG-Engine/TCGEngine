# TWI_042 — the +1/+0 requires a Barriss in play: TWI_044 heals SOR_046 (marking it healed this phase),
# but with NO Barriss controlled the healed unit gets no bonus (power stays 3).

## GIVEN
CommonSetup: bbw/grw/{myResources:2;handCardIds:TWI_044}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:3
