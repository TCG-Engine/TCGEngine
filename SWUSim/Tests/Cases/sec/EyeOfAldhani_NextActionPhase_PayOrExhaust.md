# SEC_073 The Eye of Aldhani (Event, cost 1, Vigilance, Innate/Trick)
#   "At the start of the next action phase, for each enemy unit, its controller must pay 1 resource or
#    exhaust that unit."
# P1 plays Eye of Aldhani, then both players pass to regroup and on into the NEXT action phase. There,
# P2 (the enemy of the caster) gets one MZMULTICHOOSE over its 2 units, capped at its 1 ready resource:
# it pays 1 to keep SOR_095 ready; the unselected SEC_080 is exhausted. P2 ends with 0 ready resources.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SEC_073
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2Resources: 1

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P2RESAVAILABLE:0
