# TWI_178 Planetary Invasion (Event, cost 12, Aggression) — "Exploit 3. Ready up to 3 units. Each of
# those units gets +1/+0 and gains Overwhelm for this phase." P1 has 2 exhausted units. Playing the
# event first offers Exploit 3 fodder (declined), then readies the 2 units, each getting +1/+0 and
# Overwhelm.

## GIVEN
CommonSetup: rrk/grw/{myResources:12;handCardIds:TWI_178}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:1:HASKEYWORD:Overwhelm
