# SOR_167 Force Throw — the "may deal damage" half is OPTIONAL: caster controls a Force unit (SOR_051)
# and the discarded card has cost > 0, so the damage is OFFERED, but the caster DECLINES it (AnswerDecision:-).
# Opponent holds 1 card (SOR_128) so the discard auto-resolves; nothing takes damage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2Hand: SOR_128
WithP1Hand: SOR_167
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:-
## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
